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

namespace SuiteCRMRestClient\Adapters;

/**
 * Class DummyAdapter
 *
 * This adapter provides hardcoded values for the examples.
 *
 * @package SuiteCRMRestClient\Interfaces
 */
class DummyAdapter implements ConfigurationAdapter
{
    /**
     * @var string
     */
    private $rest_url = "http://php71/SuiteCRM-github-develop/api/";

    /**
     * @var string
     */
    private $rest_user = "admin";

    /**
     * @var string
     */
    private $rest_pass = "suitecrm";

    /**
     * @var string
     */
    private $rest_client = "API-4c59-f678-cecc-6594-5a8d9c704473";

    /**
     * @var string
     */
    private $rest_secret = "secret";

    /**
     * @var string
     */
    private $grant_type = "password";

    /**
     * @return string
     */
    public function getGrantType()
    {
        return $this->grant_type;
    }

    /**
     * @return string
     */
    public function getUserID()
    {
        return $this->rest_client;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->rest_pass;
    }

    /**
     * @return string
     */
    public function getURL()
    {
        return $this->rest_url;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->rest_secret;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->rest_user;
    }

    /**
     * @param \Exception $e
     */
    public function handleException(\Exception $e)
    {
        echo $e->getMessage();
        echo $e->getTraceAsString();
    }
}