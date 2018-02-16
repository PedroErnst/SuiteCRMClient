<?php
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */

namespace SuiteCRMRestClient;
use SuiteCRMRestClient\Interfaces\ConfigurationAdapter;
use GuzzleHttp\Client;

class SuiteCRMRestClient
{
    private static $singleton;

    private $token_type;

    private $token_expires;

    private $access_token;

    private $refresh_token;

    private $config;

    private $lastUrl;

    public function __construct(ConfigurationAdapter $configurationAdapter)
    {
        $this->config = $configurationAdapter;
    }

    private function cleanUrl($url)
    {
        $url = rtrim($url, '/');
        $url .= '/';
        $url = str_replace('/api/', '', $url);
        $url .= '/api/';
        return $url;
    }

    public static function init(ConfigurationAdapter $adapter)
    {
        self::getInstance($adapter);
    }

    public static function getInstance(ConfigurationAdapter $adapter = null)
    {
        if (!self::$singleton) {
            if (!$adapter) {
                die("Calling uninitialized client without Adapter.");
            }
            self::$singleton = new self($adapter);
        }
        return self::$singleton;
    }

    /**
     * @param $api_route
     * @param array $params
     * @param string $type
     * @return mixed
     */
    private function rest_request($api_route, $params = array(), $type = 'GET')
    {
        $result = '';
        try {
            $result = $this->sendRequest($api_route, $params, $type);
        }
        catch (\Exception $e) {
            $this->config->handleException($e);
        }

        return json_decode($result, true);
    }

    private function isLoggedIn()
    {
        return isset($this->token_type);
    }

    /**
     * @return bool
     */
    public function login()
    {
        if (!$this->isLoggedIn()) {
            try {
                $this->processLogin();
            }
            catch (\Exception $e) {
                $this->config->handleException($e);
            }
        }
        return $this->isLoggedIn();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function processLogin()
    {

        $params = array(
            'grant_type' => $this->config->getGrantType(),
            'client_id' => $this->config->getUserID(),
            'client_secret' => $this->config->getSecret(),
            'username' => $this->config->getUser(),
            'password' => $this->config->getPassword(),
            'scope' => ''
        );
        $response_data = json_decode($this->sendRequest('oauth/access_token', $params, 'POST'), true);

        if (!empty($response_data['error'])) {
            throw new \Exception(
                "Failed to connect to SuiteCRM. Please check your settings. (Error: "
                . $response_data['error']
                . ', '
                . $response_data['message']
                . ')'
            );
        }

        $this->token_type = $response_data['token_type'];
        $this->token_expires = $response_data['expires_in'];
        $this->access_token = $response_data['access_token'];
        $this->refresh_token = $response_data['refresh_token'];

        return $this->isLoggedIn();
    }

    private function sendRequest($api_route, $params, $type = 'GET')
    {
        $this->lastUrl = $this->cleanUrl($this->config->getURL()) . $api_route;

        $headers = [
            'Content-type' => 'application/vnd.api+json',
            'Accept' => 'application/vnd.api+json',
        ];

        if ($this->isLoggedIn()) {
            $headers['Authorization'] = 'Bearer ' . $this->access_token;
        }

        $client = new Client();

        $options = [
            'headers' => $headers,
            'json' => $params,
        ];

        $result = $client->request($type, $this->lastUrl, $options);

        return $result->getBody();
    }

    public function getEntry($module, $id, $fields = [])
    {
        $fieldStr = '';
        if (count($fields)) {
            $fieldStr = '&fields[' . $module . ']' . implode(',', $fields);
        }

        $url = 'v8/modules/' . $module . '/' . $id . $fieldStr;
        $result = $this->rest_request($url);

        return $this->evaluateResult($result);
    }

    private function evaluateResult($result, $key = '')
    {
        try {
            if (isset($result['errors']) && count($result['errors'])) {
                throw new \Exception("Error while communicating with SuiteCRM: " . $result['errors'][0]['title']);
            }
        }
        catch (\Exception $e) {
            $this->config->handleException($e);
        }
        if ($key) {
            return $result[$key];
        }
        return $result;
    }

    public function getEntries($module, Array $ids)
    {

        $url = 'v8/modules/' . $module . '?filter[' . $module . ']=' . implode(',', $ids);
        $result = $this->rest_request($url);
        return $this->evaluateResult($result);
    }

    public function getApplicationLanguage()
    {

        $url = 'v8/modules/meta/languages';
        $result = $this->rest_request($url);

        $data = $this->evaluateResult($result);

        return $data['meta']['application']['language'];
    }

    public function setEntry($module, $data)
    {
        $id = isset($data['id']) ? $data['id'] : '';

        $postVars = array(
            'data' => array(
                'type' => $module,
                'attributes' => $data
            )
        );

        if ($id) {
            $postVars['data']['id'] = $id;
            $url = 'v8/modules/' . $module . '/' . $id;
            $result = $this->rest_request($url, $postVars, 'PATCH');
        } else {
            $url = 'v8/modules/' . $module;
            $result = $this->rest_request($url, $postVars, 'POST');
        }

        return $this->evaluateResult($result);
    }

    public function setRelationship($module1, $module1_id, $module2, $module2_id)
    {
        $data = array(
            'data' => array(
                'id' => $module2_id,
                'type' => $module2,
            )
        );

        $url = 'v8/modules/' . $module1 . '/' . $module1_id . '/relationships/' . $module2;
        $result = $this->rest_request($url, $data, 'POST');

        return $this->evaluateResult($result);
    }

    public function getRelationships($module_name, $module_id, $related_module)
    {
        $url = 'v8/modules/' . $module_name . '/' . $module_id . '/relationships/' . $related_module;
        $result = $this->rest_request($url);

        return $this->evaluateResult($result, 'data');
    }
}
