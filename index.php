<?php
/*
 *  webpa-lti - WebPA module to add LTI support
 *  Copyright (C) 2019  Stephen P Vickers
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: stephen@spvsoftwareproducts.com
 */

###
###  Process an LTI launch request
###

use ceLTIc\LTI\ToolProvider;
use ceLTIc\LTI\DataConnector\DataConnector;

include_once('../../includes/inc_global.php');
require_once(DOC__ROOT . 'includes/classes/class_authenticator.php');

require_once('vendor/autoload.php');
require_once('setting.php');

class WebPA_ToolProvider extends ToolProvider
{

    protected function onLaunch()
    {

        global $DB;
#
### Check sufficient data has been passed
#
        $resource_link_id = $this->resourceLink->getId();
        if (!isset($this->resourceLink->title) || empty($this->resourceLink->title)) {
            $this->reason = 'Missing resource link title.';
            return FALSE;
        }

        $user_id = $this->userResult->getId();
        if (empty($user_id) || empty($this->userResult->firstname) || empty($this->userResult->lastname)) {
            $this->reason = 'Missing user ID or name.';
            return FALSE;
        }
#
### Create/update module
#
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . 'module SET module_title = ' . DataConnector::quoted($this->resourceLink->title) . ', ' .
            "source_id = '{$this->resourceLink->getConsumer()->getKey()}', module_code = '{$resource_link_id}' ";
        $sql .= 'ON DUPLICATE KEY UPDATE module_title = ' . DataConnector::quoted($this->resourceLink->title);
        $DB->execute($sql);
        $sql = 'SELECT module_id FROM ' . APP__DB_TABLE_PREFIX . "module WHERE source_id = '{$this->resourceLink->getConsumer()->getKey()}' AND module_code = '{$resource_link_id}'";
        $module_id = $DB->fetch_value($sql);
#
### Create/update user
#
        if ($this->userResult->isStaff()) {
            $usertype = 'T';
        } else if ($this->userResult->isLearner()) {
            $usertype = 'S';
        } else {
            $this->reason = 'Invalid role, you must be either a member of staff or a learner.';
            return FALSE;
        }
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . "user SET forename = '{$this->userResult->firstname}', lastname = '{$this->userResult->lastname}', email = '{$this->userResult->email}', " .
            "source_id = '{$this->consumer->getKey()}', username = '{$this->userResult->getId()}', password = '" . md5(DataConnector::getRandomString()) . "', " .
            "last_module_id = {$module_id}, admin = 0 ";
        $sql .= "ON DUPLICATE KEY UPDATE forename = '{$this->userResult->firstname}', lastname = '{$this->userResult->lastname}', email = '{$this->userResult->email}'";
        $DB->execute($sql);
        $sql = 'SELECT user_id FROM ' . APP__DB_TABLE_PREFIX . "user WHERE source_id = '{$this->consumer->getKey()}' AND username = '{$this->userResult->getId()}'";
        $user_id = $DB->fetch_value($sql);
#
### Create enrolment
#
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . "user_module SET user_id = {$user_id}, module_id = {$module_id}, user_type = '{$usertype}' ";
        $sql .= "ON DUPLICATE KEY UPDATE  user_type = '{$usertype}'";

        $DB->execute($sql);
#
### Login user
#
        $auth = new Authenticator();
        $sql = 'SELECT * FROM ' . APP__DB_TABLE_PREFIX . "user WHERE user_id = {$user_id}";
        if (!$auth->initialise($sql) || $auth->is_disabled()) {

            $this->reason = 'Sorry unable to log you in, your account does not exist or has been disabled.';
            return FALSE;
        } else {
#
### Save session data
#
            $_SESSION['_user_id'] = $user_id;
            $_SESSION['_source_id'] = $this->resourceLink->getKey();
            $_SESSION['_user_type'] = $usertype;
            $_SESSION['_module_id'] = $module_id;
            $_SESSION['_user_source_id'] = $this->userResult->getResourceLink()->getKey();
            $_SESSION['_user_context_id'] = $this->userResult->getResourceLink()->getId();
            if (strtolower(fetch_POST('launch_presentation_document_target', 'window')) != 'window') {
                $_SESSION['logout_url'] = $this->returnUrl;
                $_SESSION['_no_header'] = TRUE;
            }
            $value = $this->resourceLink->getSetting('custom_logo', '');
            if (!empty($value)) {
                if ((substr($value, 0, 7) != 'http://') && (substr($value, 0, 8) != 'https://')) {
                    $value = APP__WWW . '/images/' . $value;
                }
                $_SESSION['branding_logo'] = $value;
            }
            $value = $this->resourceLink->getSetting('custom_logo_width', '');
            if (!empty($value)) {
                $_SESSION['branding_logo.width'] = $value;
            }
            $value = $this->resourceLink->getSetting('custom_logo_height', '');
            if (!empty($value)) {
                $_SESSION['branding_logo.height'] = $value;
            }
            $value = $this->resourceLink->getSetting('custom_name', '');
            if (!empty($value)) {
                $_SESSION['branding_name'] = $value;
            }
            $value = $this->resourceLink->getSetting('custom_css', '');
            if (empty($value) && !empty($this->consumer->css_path)) {
                $value = $this->consumer->css_path;
            }
            if (!empty($value)) {
                $_SESSION['branding_css'] = $value;
            }
            $value = $this->resourceLink->getSetting('custom_email_help', '');
            if (!empty($value)) {
                $_SESSION['branding_email.help'] = $value;
            }
            $value = $this->resourceLink->getSetting('custom_email_noreply', '');
            if (!empty($value)) {
                $_SESSION['branding_email.noreply'] = $value;
            }
            $value = $this->resourceLink->getSetting('custom_return_menu_text', '');
            if (!empty($value)) {
                $_SESSION['branding_return_menu_text'] = $value;
            }

            logEvent('Login');
            logEvent('Enter module', $module_id);
#
### Return redirect URL
#
            $this->redirectUrl = APP__WWW . '/cookie.php?id=' . urlencode($this->userResult->getId()) . '&url=' . urlencode($this->returnUrl);
            return TRUE;
        }
    }

}

#
### Cancel any existing session
#
$_SESSION = array();
session_destroy();
session_start();

#
### Open database
#
$DB->open();

#
### Perform LTI connection
#
$dataconnector = DataConnector::getDataConnector($DB->getConnection(), APP__DB_TABLE_PREFIX);
$tool = new WebPA_ToolProvider($dataconnector);
$tool->allowSharing = ALLOW_SHARING;
$tool->defaultEmail = DEFAULT_EMAIL;
$tool->handleRequest();

#
### Close database
#
$DB->close();

exit;
?>