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
use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Profile;
use ceLTIc\LTI\Service;
use ceLTIc\LTI\Util;

class WebPA_Tool extends Tool
{

    public function __construct($data_connector)
    {
        parent::__construct($data_connector);

        $this->baseUrl = APP__WWW . '/mod/lti/';

        $this->vendor = new Profile\Item('lboro', 'Loughborough University', 'Loughborough University',
            'http://webpaproject.lboro.ac.uk/');
        $this->product = new Profile\Item('659c790e-4071-4ef0-9f6e-248dab0b37d4', 'WebPA',
            'An open source online peer assessment tool', 'http://webpaproject.lboro.ac.uk/', APP__VERSION);

        $requiredMessages = array(new Profile\Message('basic-lti-launch-request', 'index.php',
                array('User.id', 'Membership.role', 'Person.name.full', 'Person.name.family', 'Person.name.given')));
        $optionalMessages = array(new Profile\Message('ContentItemSelectionRequest', 'index.php',
                array('User.id', 'Membership.role', 'Person.name.full', 'Person.name.family', 'Person.name.given'))
        );

        $this->resourceHandlers[] = new Profile\ResourceHandler(
            new Profile\Item('webpa', 'WebPA', 'An open source online peer assessment tool'), 'logo50.png', $requiredMessages,
            $optionalMessages);

        $this->setParameterConstraint('resource_link_id', true, 255, array('basic-lti-launch-request'));
        $this->setParameterConstraint('user_id', true);

        $this->allowSharing = ALLOW_SHARING;
        $this->defaultEmail = DEFAULT_EMAIL;

        $this->signatureMethod = LTI_SIGNATURE_METHOD;
        $this->jku = $this->baseUrl . 'jwks.php';
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
        $dbConnection = lti_getConnection();
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . 'module SET module_title = ?, ' .
            'source_id = ?, module_code = ? ON DUPLICATE KEY UPDATE module_title = ?';
        $title = $this->resourceLink->title;
        $key = $this->resourceLink->getPlatform()->getKey();
        $stmt = $dbConnection->prepare($sql);
        $stmt->bind_param('ssss', $title, $key, $resource_link_id, $title);
        $stmt->execute();
        $sql = 'SELECT module_id FROM ' . APP__DB_TABLE_PREFIX . 'module WHERE (source_id = ?) AND (module_code = ?)';
        $stmt = $dbConnection->prepare($sql);
        $stmt->bind_param('ss', $key, $resource_link_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $module_id = $row['module_id'];
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
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . 'user SET forename = ?, lastname = ?, email = ?, ' .
            'source_id = ?, username = ?, password = ?, ' .
            'last_module_id = ?, admin = 0 ' .
            'ON DUPLICATE KEY UPDATE forename = ?, lastname = ?, email = ?';
        $firstname = $this->userResult->firstname;
        $lastname = $this->userResult->lastname;
        $email = $this->userResult->email;
        $id = $this->userResult->getId();
        $key = $this->resourceLink->getPlatform()->getKey(); //$this->consumer->getKey();
        $password = md5(Util::getRandomString());
        $stmt = $dbConnection->prepare($sql);
        $stmt->bind_param('ssssssisss', $firstname, $lastname, $email, $key, $id, $password, $module_id, $firstname, $lastname,
            $email);
        $stmt->execute();
        $sql = 'SELECT user_id FROM ' . APP__DB_TABLE_PREFIX . 'user WHERE (source_id = ?) AND (username = ?)';
        $stmt = $dbConnection->prepare($sql);
        $stmt->bind_param('ss', $key, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
#
### Create enrolment
#
        $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . 'user_module SET user_id = ?, module_id = ?, user_type = ? ' .
            'ON DUPLICATE KEY UPDATE  user_type = ?';
        $stmt = $dbConnection->prepare($sql);
        $stmt->bind_param('iiss', $user_id, $module_id, $usertype, $usertype);
        $stmt->execute();
#
### Login user
#
        $auth = new Authenticator();
        if (!class_exists('Doctrine\DBAL\ParameterType')) {
            $param = 'SELECT * FROM ' . APP__DB_TABLE_PREFIX . "user WHERE user_id = {$user_id}";
        } else {
            $sql = 'SELECT * FROM ' . APP__DB_TABLE_PREFIX . 'user WHERE (user_id = ?)';
            $stmt = $dbConnection->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $param = $result->fetch_assoc();
        }
        if (!$auth->initialise($param) || $auth->is_disabled()) {
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
            if (strtolower(lti_fetch_POST('launch_presentation_document_target', 'window')) != 'window') {
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

    protected function onRegistration()
    {
        $platformConfig = $this->getPlatformConfiguration();
        if ($this->ok) {
            $toolConfig = $this->getConfiguration($platformConfig);
            $registrationConfig = $this->sendRegistration($platformConfig, $toolConfig);
            if ($this->ok) {
                $now = time();
                $platform = $this->getPlatformToRegister($platformConfig, $registrationConfig, false);
                do {
                    $key = self::getGUID();
                    $consumer = Platform::fromConsumerKey($key, $this->dataConnector);
                } while (!is_null($consumer->created));
                $platform->setKey($key);
                $platform->secret = Util::getRandomString(32);
                $platform->name = 'Trial (' . date('Y-m-d H:i:s', $now) . ')';
                $platform->protected = true;
                if (defined('AUTO_ENABLE') && AUTO_ENABLE) {
                    $platform->enabled = true;
                }
                if (defined('ENABLE_FOR_DAYS') && (ENABLE_FOR_DAYS > 0)) {
                    $platform->enableFrom = $now;
                    $platform->enableUntil = $now + (ENABLE_FOR_DAYS * 24 * 60 * 60);
                }
                $this->ok = $platform->save();
            }
        }
        $this->getRegistrationResponsePage($toolConfig);
        $this->ok = true;
    }

    private static function getGUID()
    {

        $str = strtoupper(Util::getRandomString(32));
        $str = substr($str, 0, 8) . '-' . substr($str, 8, 4) . '-' . substr($str, 12, 4) . '-' . substr($str, 16, 4) . '-' . substr($str,
                20);

        return $str;
    }

}

?>