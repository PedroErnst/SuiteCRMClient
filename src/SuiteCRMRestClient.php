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

include_once __DIR__ . '/Adapters/ConfigurationAdapter.php';
include_once __DIR__ . '/Adapters/DummyAdapter.php';

use SuiteCRMRestClient\Adapters\ConfigurationAdapter;
use SuiteCRMRestClient\Adapters\DummyAdapter;

/**
 * Class SuiteCRMRestClient
 * @package SuiteCRMRestClient
 */
class SuiteCRMRestClient
{
    /**
     * @var SuiteCRMRestClient
     */
    private static $singleton;

    /**
     * @var string
     */
    private $token_type;

    /**
     * @var string
     */
    private $token_expires;

    /**
     * @var string
     */
    private $access_token;

    /**
     * @var string
     */
    private $refresh_token;

    /**
     * @var ConfigurationAdapter
     */
    private $config;

    /**
     * @var string
     */
    private $lastUrl;

    /**
     * SuiteCRMRestClient constructor.
     * @param ConfigurationAdapter $configurationAdapter
     */
    public function __construct(ConfigurationAdapter $configurationAdapter)
    {
        $this->config = $configurationAdapter;
    }

    /**
     * @param ConfigurationAdapter $adapter
     */
    public static function init(ConfigurationAdapter $adapter)
    {
        self::$singleton = new self($adapter);
    }

    /**
     * @return SuiteCRMRestClient
     */
    public static function getInstance()
    {
        if (!self::$singleton) {
            die ('Rest Client not initialized. Call init() first.');
        }
        return self::$singleton;
    }

    /**
     * @return bool
     */
    public function login()
    {
        if (!$this->isLoggedIn()) {
            try {
                $this->processLogin();
            } catch (\Exception $e) {
                $this->config->handleException($e);
            }
        }
        return $this->isLoggedIn();
    }

    /**
     * @return bool
     */
    private function isLoggedIn()
    {
        return isset($this->token_type);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function processLogin()
    {

        $params = [
            'grant_type' => $this->config->getGrantType(),
            'client_id' => $this->config->getUserID(),
            'client_secret' => $this->config->getSecret(),
            'username' => $this->config->getUser(),
            'password' => $this->config->getPassword(),
            'scope' => ''
        ];
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

    /**
     * @param string $api_route
     * @param array $params
     * @param string $type
     * @return array
     */
    private function sendRequest($api_route, $params, $type = 'GET')
    {
        if (class_exists('GuzzleHttp\Client')) {
            return $this->sendGuzzleRequest($api_route, $params, $type);
        }
        return $this->sendCurlRequest($api_route, $params, $type);
    }

    /**
     * @param string $api_route
     * @param array $params
     * @param string $type
     * @return array
     */
    private function sendGuzzleRequest($api_route, $params, $type = 'GET')
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


    /**
     * @param string $api_route
     * @param array $params
     * @param string $type
     * @return array
     */
    private function sendCurlRequest($api_route, $params, $type = 'GET')
    {
        ob_start();
        $ch = curl_init();

        $this->lastUrl = $this->cleanUrl($this->config->getURL()) . $api_route;

        $postStr = json_encode($params);
        $header = array(
            'Content-type: application/vnd.api+json',
            'Accept: application/vnd.api+json',
        );

        if ($type != 'GET') {
            $header[] = 'Content-Length: ' . strlen($postStr);
        }

        if ($this->isLoggedIn()) {
            $header[] = 'Authorization: Bearer ' . $this->access_token;
        }

        curl_setopt($ch, CURLOPT_URL, $this->lastUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_COOKIE, 'XDEBUG_SESSION_START=13537');

        $output = curl_exec($ch);
        curl_close($ch);
        ob_end_flush();

        return $output;
    }

    /**
     * @param string $url
     * @return string
     */
    private function cleanUrl($url)
    {
        $url = rtrim($url, '/');
        $url .= '/';
        $url = str_replace('/api/', '', $url);
        $url .= '/api/';
        return $url;
    }

    /**
     * @param string $module
     * @param string $id
     * @param array $fields
     * @return array
     */
    public function getEntry($module, $id, $fields = [])
    {
        $fieldStr = '';
        if (count($fields)) {
            $fieldStr = '&fields[' . $module . ']' . implode(',', $fields);
        }

        $url = 'v8/modules/' . $module . '/' . $id . $fieldStr;
        $result = $this->restRequest($url);

        return $this->evaluateResult($result);
    }

    /**
     * @param string $api_route
     * @param array $params
     * @param string $type
     * @return array
     */
    private function restRequest($api_route, $params = [], $type = 'GET')
    {
        $result = '';

        $this->login();

        try {
            $result = $this->sendRequest($api_route, $params, $type);
        } catch (\Exception $e) {
            $this->config->handleException($e);
        }

        return json_decode($result, true);
    }

    /**
     * @param array $result
     * @param string $key
     * @return array
     */
    private function evaluateResult($result, $key = '')
    {
        try {
            if (isset($result['errors']) && count($result['errors'])) {
                throw new \Exception("Error while communicating with SuiteCRM: " . $result['errors'][0]['title']);
            }
        } catch (\Exception $e) {
            $this->config->handleException($e);
        }
        if ($key) {
            return $result[$key];
        }
        return $result;
    }

    /**
     * @param string $module
     * @param array $ids
     * @return mixed
     */
    public function getEntries($module, Array $ids)
    {

        $url = 'v8/modules/' . $module . '?filter[' . $module . ']=' . implode(',', $ids);
        $result = $this->restRequest($url);
        return $this->evaluateResult($result);
    }

    /**
     * @return mixed
     */
    public function getApplicationLanguage()
    {

        $url = 'v8/modules/meta/languages';
        $result = $this->restRequest($url);

        $data = $this->evaluateResult($result);

        return $data['meta']['application']['language'];
    }

    /**
     * @param string $module
     * @param array $data
     * @return array
     */
    public function setEntry($module, $data)
    {
        $id = isset($data['id']) ? $data['id'] : '';

        $postVars = [
            'data' => [
                'type' => $module,
                'attributes' => $data
            ]
        ];

        if ($id) {
            $postVars['data']['id'] = $id;
            $url = 'v8/modules/' . $module . '/' . $id;
            $result = $this->restRequest($url, $postVars, 'PATCH');
        } else {
            $url = 'v8/modules/' . $module;
            $result = $this->restRequest($url, $postVars, 'POST');
        }

        return $this->evaluateResult($result);
    }

    /**
     * @param string $module1
     * @param string $module1_id
     * @param string $module2
     * @param string $module2_id
     * @return array
     */
    public function setRelationship($module1, $module1_id, $module2, $module2_id)
    {
        $data = [
            'data' => [
                'id' => $module2_id,
                'type' => $module2,
            ]
        ];

        $url = 'v8/modules/' . $module1 . '/' . $module1_id . '/relationships/' . $module2;
        $result = $this->restRequest($url, $data, 'POST');

        return $this->evaluateResult($result);
    }

    /**
     * @param string $module_name
     * @param string $module_id
     * @param string $related_module
     * @return array
     */
    public function getRelationships($module_name, $module_id, $related_module)
    {
        $url = 'v8/modules/' . $module_name . '/' . $module_id . '/relationships/' . $related_module;
        $result = $this->restRequest($url);

        return $this->evaluateResult($result, 'data');
    }
}
