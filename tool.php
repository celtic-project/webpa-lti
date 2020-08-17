<?php
/*
 *  webpa-lti - WebPA module to add LTI support
 *  Copyright (C) 2020  Stephen P Vickers
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
###  LTI tool definition for WebPA
###

use ceLTIc\LTI\Tool;
use ceLTIc\LTI\DataConnector\DataConnector;
use ceLTIc\LTI\Service;
use ceLTIc\LTI\Util;

class WebPA_Tool extends Tool
{

    public function __construct($data_connector)
    {
        parent::__construct($data_connector);

        $this->setParameterConstraint('resource_link_id', true, 40, array('basic-lti-launch-request'));
        $this->setParameterConstraint('user_id', true);

        $this->allowSharing = ALLOW_SHARING;
        $this->defaultEmail = DEFAULT_EMAIL;

        $this->signatureMethod = LTI_SIGNATURE_METHOD;
        $this->kid = LTI_KID;
        $this->rsaKey = LTI_PRIVATE_KEY;
        $this->requiredScopes = array(
            Service\Membership::$SCOPE,
            Service\Score::$SCOPE
        );
    }

    protected function onLaunch()
    {

        global $DB;
#
### Check sufficient data has been passed
#
        $resource_link_id = $this->resourceLink->getId();
        if (!isset($this->resourceLink->title) || empty($this->resourceLink->title)) {
            $this->reason = 'Missing resource link title.';
            return false;
        }

        $user_id = $this->userResult->getId();
        if (empty($user_id) || empty($this->userResult->firstname) || empty($this->userResult->lastname)) {
            $this->reason = 'Missing user ID or name.';
            return false;
        }
#
### Create/update module
#
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . 'module SET module_title = ' . DataConnector::quoted($this->resourceLink->title) . ', ' .
            "source_id = '{$this->resourceLink->getPlatform()->getKey()}', module_code = '{$resource_link_id}' ";
        $sql .= 'ON DUPLICATE KEY UPDATE module_title = ' . DataConnector::quoted($this->resourceLink->title);
        $DB->execute($sql);
        $sql = 'SELECT module_id FROM ' . APP__DB_TABLE_PREFIX . "module WHERE source_id = '{$this->resourceLink->getPlatform()->getKey()}' AND module_code = '{$resource_link_id}'";
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
            return false;
        }
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . "user SET forename = '{$this->userResult->firstname}', lastname = '{$this->userResult->lastname}', email = '{$this->userResult->email}', " .
            "source_id = '{$this->consumer->getKey()}', username = '{$this->userResult->getId()}', password = '" . md5(Util::getRandomString()) . "', " .
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
            return false;
        } else {
#
### Save session data
#
            $_SESSION['_user_id'] = $user_id;
            $_SESSION['_source_id'] = $this->resourceLink->getPlatform()->getKey();
            $_SESSION['_user_type'] = $usertype;
            $_SESSION['_module_id'] = $module_id;
            $_SESSION['_user_source_id'] = $this->userResult->getResourceLink()->getPlatform()->getKey();
            $_SESSION['_user_context_id'] = $this->userResult->getResourceLink()->getId();
            if (strtolower(fetch_POST('launch_presentation_document_target', 'window')) != 'window') {
                $_SESSION['logout_url'] = $this->returnUrl;
                $_SESSION['_no_header'] = true;
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
            return true;
        }
    }

}

?>