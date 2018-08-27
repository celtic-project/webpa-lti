<?php
/*
 *  webpa-lti - WebPA module to add LTI support
 *  Copyright (C) 2013  Stephen P Vickers
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
 *
 *  Version history:
 *    1.0.00   4-Jul-12  Initial release
 *    1.1.00  10-Feb-13  Added option to override name of "logout" option
 *                       Updated for WebPA cookie check
*/

###
###  Process an LTI launch request
###

  include_once('../../includes/inc_global.php');
  require_once(DOC__ROOT . 'includes/classes/class_authenticator.php');

  require_once('setting.php');
  require_once('lib/LTI_Tool_Provider.php');

#
### Open database
#
  $DB->open();

#
### Perform LTI connection
#
  $tool = new LTI_Tool_Provider(array('connect' => 'doConnect'), APP__DB_TABLE_PREFIX);
  $tool->allowSharing = ALLOW_SHARING;
  $tool->defaultEmail = DEFAULT_EMAIL;
  $tool->execute();

#
### Close database
#
  $DB->close();

  exit;


###
#    Define callback function for handling verified connection
###
  function doConnect($tool_provider) {

    global $DB;
#
### Check sufficient data has been passed
#
    $resource_link_id = $tool_provider->resource_link->getId();
    if (!isset($tool_provider->resource_link->title) || empty($tool_provider->resource_link->title)) {
      $tool_provider->reason = 'Missing resource link title.';
      return FALSE;
    }

    $user_id = $tool_provider->user->getId();
    if (empty($user_id) || empty($tool_provider->user->firstname) || empty($tool_provider->user->lastname)) {
      $tool_provider->reason = 'Missing user ID or name.';
      return FALSE;
    }
#
### Create/update module
#
    $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . 'module SET module_title = '. LTI_Data_Connector::quoted($tool_provider->resource_link->title) . ', ' .
           "source_id = '{$tool_provider->resource_link->getConsumer()->getKey()}', module_code = '{$resource_link_id}' ";
    $sql .= 'ON DUPLICATE KEY UPDATE module_title = ' . LTI_Data_Connector::quoted($tool_provider->resource_link->title);
    $DB->execute($sql);
    $sql = 'SELECT module_id FROM ' . APP__DB_TABLE_PREFIX . "module WHERE source_id = '{$tool_provider->resource_link->getConsumer()->getKey()}' AND module_code = '{$resource_link_id}'";
    $module_id = $DB->fetch_value($sql);
#
### Create/update user
#
    if ($tool_provider->user->isStaff()) {
      $usertype = 'T';
    } else if ($tool_provider->user->isLearner()) {
      $usertype = 'S';
    } else {
      $tool_provider->reason = 'Invalid role, you must be either a member of staff or a learner.';
      return FALSE;
    }
    $sql = 'INSERT INTO ' . APP__DB_TABLE_PREFIX . "user SET forename = '{$tool_provider->user->firstname}', lastname = '{$tool_provider->user->lastname}', email = '{$tool_provider->user->email}', " .
           "source_id = '{$tool_provider->consumer->getKey()}', username = '{$tool_provider->user->getId()}', password = '" . md5(LTI_Data_Connector::getRandomString()) . "', " .
           "last_module_id = {$module_id}, admin = 0 ";
    $sql .= "ON DUPLICATE KEY UPDATE forename = '{$tool_provider->user->firstname}', lastname = '{$tool_provider->user->lastname}', email = '{$tool_provider->user->email}'";
    $DB->execute($sql);
    $sql = 'SELECT user_id FROM ' . APP__DB_TABLE_PREFIX . "user WHERE source_id = '{$tool_provider->consumer->getKey()}' AND username = '{$tool_provider->user->getId()}'";
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

      $tool_provider->reason = 'Sorry unable to log you in, your account does not exist or has been disabled.';
      return FALSE;

    } else {
#
### Save session data
#
      $_SESSION['_user_id'] = $user_id;
      $_SESSION['_source_id'] = $tool_provider->resource_link->getConsumer()->getKey();
      $_SESSION['_user_type'] = $usertype;
      $_SESSION['_module_id'] = $module_id;
      $_SESSION['_user_source_id'] = $tool_provider->user->getResourceLink()->getConsumer()->getKey();
      $_SESSION['_user_context_id'] = $tool_provider->user->getResourceLink()->getId();
      if (strtolower(fetch_POST('launch_presentation_document_target', 'window')) != 'window') {
        $_SESSION['logout_url'] = $tool_provider->return_url;
        $_SESSION['_no_header'] = TRUE;
      }
      $value = $tool_provider->resource_link->getSetting('custom_logo', '');
      if (!empty($value)) {
        if ((substr($value, 0, 7) != 'http://') && (substr($value, 0, 8) != 'https://')) {
          $value = APP__WWW . '/images/' . $value;
        }
        $_SESSION['branding_logo'] = $value;
      }
      $value = $tool_provider->resource_link->getSetting('custom_logo_width', '');
      if (!empty($value)) {
        $_SESSION['branding_logo.width'] = $value;
      }
      $value = $tool_provider->resource_link->getSetting('custom_logo_height', '');
      if (!empty($value)) {
        $_SESSION['branding_logo.height'] = $value;
      }
      $value = $tool_provider->resource_link->getSetting('custom_name', '');
      if (!empty($value)) {
        $_SESSION['branding_name'] = $value;
      }
      $value = $tool_provider->resource_link->getSetting('custom_css', '');
      if (empty($value) && !empty($tool_provider->consumer->css_path)) {
        $value = $tool_provider->consumer->css_path;
      }
      if (!empty($value)) {
        $_SESSION['branding_css'] = $value;
      }
      $value = $tool_provider->resource_link->getSetting('custom_email_help', '');
      if (!empty($value)) {
        $_SESSION['branding_email.help'] = $value;
      }
      $value = $tool_provider->resource_link->getSetting('custom_email_noreply', '');
      if (!empty($value)) {
        $_SESSION['branding_email.noreply'] = $value;
      }
      $value = $tool_provider->resource_link->getSetting('custom_return_menu_text', '');
      if (!empty($value)) {
        $_SESSION['branding_return_menu_text'] = $value;
      }

      logEvent('Login');
      logEvent('Enter module', $module_id);
#
### Return redirect URL
#
      return APP__WWW . '/cookie.php?id=' . urlencode($tool_provider->user->getId()) . '&url=' . urlencode($tool_provider->return_url);
    }
  }

?>