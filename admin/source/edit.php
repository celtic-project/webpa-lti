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
###  Page to allow details of a source to be edited
###

use ceLTIc\LTI\Platform;
use ceLTIc\LTI\Util;

require_once('../../includes.php');

#
### Option only available for administrators
#
if (!check_user($_user, APP__USER_TYPE_ADMIN)) {
    header('Location:' . APP__WWW . '/logout.php?msg=denied');
    exit;
}
#
### Get query parameters
#
$key = trim(lti_fetch_GET('s', ''));
#
### Initialise LTI Platform
#
$secret = trim(lti_fetch_POST('source_secret'));
if (!empty($key)) {
    $platform = Platform::fromConsumerKey($key, $dataconnector);
    if (empty($secret)) {
        $secret = $platform->secret;
    }
} else {
    $platform = new Platform($dataconnector);
}
if (empty($secret)) {
    $secret = Util::getRandomString(32);
    $platform->secret = $secret;
}
#
### Set the page information
#
$UI->menu_selected = 'lti sources';
if (empty($key)) {
    $UI->page_title = APP__NAME . ' Add source system';
    $UI->breadcrumbs = array('home' => '../../../../admin', 'lti sources' => './', 'add' => null,);
} else {
    $UI->page_title = APP__NAME . ' Edit source system';
    $UI->breadcrumbs = array('home' => '../../../../admin', 'lti sources' => './', 'edit' => null,);
}
$UI->help_link = '?q=node/237';
#
### Display page
#
$UI->head();
$UI->body();
$UI->content_start();
$sScreenMsg = '';
#
### Check for save request
#
$action = trim(lti_fetch_POST('save'));
if ($action) {
    switch ($action) {
        case 'Save Changes':
            $platform->ltiVersion = trim(lti_fetch_POST('source_ltiversion'));
            if (empty($key)) {
                $key = trim(lti_fetch_POST('source_key'));
                $platform->setKey($key);
            }
            $name = trim(lti_fetch_POST('source_name'));
            $platformid = trim(lti_fetch_POST('source_platformid'));
            $clientid = trim(lti_fetch_POST('source_clientid'));
            $deploymentid = trim(lti_fetch_POST('source_deploymentid'));
            $enabled = (lti_fetch_POST('source_enabled') == '1');
            $protected = (lti_fetch_POST('source_protected') == '1');
            $debugMode = (lti_fetch_POST('source_debug') == '1');
            $platform->name = $name;
            $platform->secret = $secret;
            $platform->platformId = !empty($platformid) ? $platformid : null;
            $platform->clientId = !empty($clientid) ? $clientid : null;
            $platform->deploymentId = !empty($deploymentid) ? $deploymentid : null;
            $platform->authorizationServerId = trim(lti_fetch_POST('source_authorizationserverid'));
            $platform->authenticationUrl = trim(lti_fetch_POST('source_authenticationurl'));
            $platform->accessTokenUrl = trim(lti_fetch_POST('source_accesstokenurl'));
            $platform->rsaKey = trim(lti_fetch_POST('source_publickey'));
            $platform->jku = trim(lti_fetch_POST('source_jku'));
            $platform->enabled = $enabled;
            $platform->protected = $protected;
            $platform->debugMode = $debugMode;
            $date = trim(lti_fetch_POST('source_from'));
            if (empty($date)) {
                $platform->enableFrom = null;
            } else {
                $time = strtotime($date);
                $platform->enableFrom = $time;
            }
            $date = trim(lti_fetch_POST('source_until'));
            if (empty($date)) {
                $platform->enableUntil = null;
            } else {
                $time = strtotime($date);
                $platform->enableUntil = $time;
            }
            $settings = $platform->getSettings();
            foreach ($settings as $name => $value) {
                if (strpos($name, 'custom_') !== 0) {
                    $platform->setSetting($name, null);
                }
            }
            $properties = trim(lti_fetch_POST('source_properties'));
            $properties = str_replace("\r\n", "\n", $properties);
            $properties = explode("\n", $properties);
            foreach ($properties as $property) {
                if (strpos($property, '=') !== false) {
                    list($name, $value) = explode('=', $property, 2);
                    if ($name) {
                        $platform->setSetting($name, $value);
                    }
                }
            }
            if (!$name) {
                $sScreenMsg = "Please complete the name field";
            } else if (!$key) {
                $sScreenMsg = "Every source must be given a unique key even if only LTI 1.3 is to be used";
            } else if (($platform->ltiVersion === Util::LTI_VERSION1P3) && (!$platformid || !$clientid || !$deploymentid)) {
                $sScreenMsg = "Please specify the platform ID, client ID and deployment ID for LTI 1.3";
            } else if (($platformid xor $clientid) || ($platformid xor $deploymentid)) {
                $sScreenMsg = "If you enter any one of platform ID, client ID or deployment ID, all three must be specified";
            } else if (!$platform->save()) {
                $sScreenMsg = "Sorry, there was an error with saving your data, please try again.";
            } else {
                $sScreenMsg = "The changes made for the source have been saved";
            }
            break;
    }
}
$v1 = Util::LTI_VERSION1;
$v1p3 = Util::LTI_VERSION1P3;
$v1Selected = ' selected';
$v1p3Selected = '';
if ($platform->ltiVersion === $v1p3) {
    $v1Selected = '';
    $v1p3Selected = ' selected';
}
?>
<p>
  Here you are able to edit the details of a source within the system. There may be some elements of
  the information which do not appear to have been completed and this will be dependant on the
  information stored in the system.
</p>

<div class="content_box">

  <?php
  if (!empty($sScreenMsg)) {
      echo "  <div class=\"success_box\">{$sScreenMsg}</div>\n";
  }
  $q = '';
  if (!empty($key)) {
      $q = '?s=' . urlencode($key);
  }
  ?>
  <form action="edit.php<?php echo $q; ?>" method="post" name="edit_source">
    <table class="option_list" style="width: 100%;">
      <tr>
        <td colspan="2">
          <h2>Platform Details</h2>
        </td>
      </tr>
      <tr>
        <td><label for="name">LTI version</label></td>
        <td>
          <select id="ltiversion" name="source_ltiversion" onchange="onVersionChange(this);">
            <option value="<?php echo $v1; ?>"<?php echo $v1Selected; ?>>1.0/1.1/1.2/2.0</option>
            <option value="<?php echo $v1p3; ?>"<?php echo $v1p3Selected; ?>>1.3</option>
          </select>
        </td>
      </tr>
      <tr>
        <td><label for="name">Name</label></td>
        <td>
          <input type="text" id="name" name="source_name" value="<?php echo $platform->name; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr>
        <td><label for="key">Key</label></td>
        <td>
          <?php
          if ($key) {
              echo "      {$key}\n";
              echo "      <input type=\"hidden\" id=\"key\" name=\"source_key\" value=\"{$key}\">\n";
          } else {
              echo "      <input type=\"text\" id=\"key\" name=\"source_key\" value=\"{$key}\" size=\"40\" maxlength=\"255\">\n";
          }
          $from = '';
          if (!is_null($platform->enableFrom)) {
              $from = date('j-M-Y H:i', $platform->enableFrom);
          }
          $until = '';
          if (!is_null($platform->enableUntil)) {
              $until = date('j-M-Y H:i', $platform->enableUntil);
          }
          $properties = '';
          $settings = $platform->getSettings();
          foreach ($settings as $name => $value) {
              if (strpos($name, 'custom_') !== 0) {
                  $properties .= "{$name}={$value}\n";
              }
          }
          ?>
        </td>
      </tr>
      <tr>
        <td><label for="secret">Secret</label></td>
        <td>
          <input type="text" id="secret" name="source_secret" value="<?php echo $platform->secret; ?>" size="50" maxlength="255">
        </td>
      </tr>

      <tr style="display: none;">
        <td><label for="platformid">Platform ID</label></td>
        <td>
          <input type="text" id="platformid" name="source_platformid" value="<?php echo $platform->platformId; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr style="display: none;">
        <td><label for="clientid">Client ID</label></td>
        <td>
          <input type="text" id="clientid" name="source_clientid" value="<?php echo $platform->clientId; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr style="display: none;">
        <td><label for="deploymentid">Deployment ID</label></td>
        <td>
          <input type="text" id="deploymentid" name="source_deploymentid" value="<?php echo $platform->deploymentId; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr style="display: none;">
        <td><label for="authorizationserverid">Authorization server ID</label></td>
        <td>
          <input type="text" id="authorizationserverid" name="source_authorizationserverid" value="<?php echo $platform->authorizationServerId; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr style="display: none;">
        <td><label for="authenticationurl">Authentication request URL</label></td>
        <td>
          <input type="text" id="authenticationurl" name="source_authenticationurl" value="<?php echo $platform->authenticationUrl; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr style="display: none;">
        <td><label for="accesstokenurl">Access token URL</label></td>
        <td>
          <input type="text" id="accesstokenurl" name="source_accesstokenurl" value="<?php echo $platform->accessTokenUrl; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr style="display: none;">
        <td><label for="publickey">Public key</label></td>
        <td><textarea id="publickey" name="source_publickey" rows="5" cols="100"><?php echo $platform->rsaKey; ?></textarea></td>
      </tr>
      <tr style="display: none;">
        <td><label for="jku">JSON webkey URL (jku)</label></td>
        <td>
          <input type="text" id="jku" name="source_jku" value="<?php echo $platform->jku; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr>
        <td><label for="protected">Protected?</label></td>
        <td>
          <input type="checkbox" id="protected" name="source_protected" value="1"<?php
          if ($platform->protected) {
              echo ' checked="checked"';
          }
          ?>>
        </td>
      </tr>
      <tr>
        <td><label for="enabled">Enabled?</label></td>
        <td>
          <input type="checkbox" id="enabled" name="source_enabled" value="1"<?php
          if ($platform->enabled) {
              echo ' checked="checked"';
          }
          ?>>
        </td>
      </tr>
      <tr>
        <td>&nbsp;&nbsp;&nbsp;<label for="from">Enable from</label></td>
        <td>
          &nbsp;&nbsp;&nbsp;<input type="text" id="from" name="source_from" value="<?php echo $from; ?>" size="20">
        </td>
      </tr>
      <tr>
        <td>&nbsp;&nbsp;&nbsp;<label for="until">Enable until</label></td>
        <td>
          &nbsp;&nbsp;&nbsp;<input type="text" id="until" name="source_until" value="<?php echo $until; ?>" size="20">
        </td>
      </tr>
      <tr>
        <td><label for="properties">Properties</label></td>
        <td><textarea id="properties" name="source_properties" rows="5" cols="100"><?php echo $properties; ?></textarea></td>
      </tr>
      <tr>
        <td><label for="debug">Debug mode?</label></td>
        <td>
          <input type="checkbox" id="debug" name="source_debug" value="1"<?php
          if ($platform->debugMode) {
              echo ' checked="checked"';
          }
          ?>>
        </td>
      </tr>
      <tr>
        <td>Created</td>
        <td><?php
          if (!empty($platform->created)) {
              echo date("d-M-Y H:i", $platform->created);
          }
          ?></td>
      </tr>
      <tr>
        <td>Last updated</td>
        <td><?php
          if (!empty($platform->updated)) {
              echo date("d-M-Y H:i", $platform->updated);
          }
          ?></td>
      </tr>
      <tr><td colspan="2"><hr/></td></tr>
      <tr>
        <td colspan="2">
          <input type="submit" value="Save Changes" name="save" id="save">
        </td>
      </tr>
    </table>
  </form>
</div>
<script type="text/javascript">
//<![CDATA[
    function onVersionChange(el) {
      if (el.selectedIndex <= 0) {
        displayv1 = 'table-row';
        displayv1p3 = 'none';
      } else {
        displayv1 = 'none';
        displayv1p3 = 'table-row';
      }
      var el = document.getElementById('key').parentElement.parentElement;
      el.style.display = displayv1;
      el = document.getElementById('secret').parentElement.parentElement;
      el.style.display = displayv1;
      el = document.getElementById('platformid').parentElement.parentElement;
      el.style.display = displayv1p3;
      el = document.getElementById('clientid').parentElement.parentElement;
      el.style.display = displayv1p3;
      el = document.getElementById('deploymentid').parentElement.parentElement;
      el.style.display = displayv1p3;
      el = document.getElementById('authorizationserverid').parentElement.parentElement;
      el.style.display = displayv1p3;
      el = document.getElementById('authenticationurl').parentElement.parentElement;
      el.style.display = displayv1p3;
      el = document.getElementById('accesstokenurl').parentElement.parentElement;
      el.style.display = displayv1p3;
      el = document.getElementById('publickey').parentElement.parentElement;
      el.style.display = displayv1p3;
      el = document.getElementById('jku').parentElement.parentElement;
      el.style.display = displayv1p3;
    }

    function doOnLoad() {
      onVersionChange(document.getElementById('ltiversion'));
    }

    window.onload = doOnLoad;
//]]>
</script>
<?php
$UI->content_end();
?>