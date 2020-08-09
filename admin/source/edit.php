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
###  Page to allow details of a source to be edited
###

use ceLTIc\LTI\ToolConsumer;

require_once("../../../../includes/inc_global.php");

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
$source = fetch_POST('source_key', fetch_GET('s', ''));
$action = fetch_POST('command');
#
### Initialise LTI Tool Consumer
#
$consumer2 = new ToolConsumer($source, $dataconnector);
#
### Set the page information
#
$UI->menu_selected = 'lti sources';
if (empty($source)) {
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
$action = fetch_POST('save');
if ($action) {
    switch ($action) {
        case 'Save Changes':
            $name = fetch_POST('source_name');
            $changed = ($name != $consumer2->name);
            $enabled = (fetch_POST('source_enabled') == '1');
            $changed = $changed || ($enabled != $consumer2->enabled);
            $protected = (fetch_POST('source_protected') == '1');
            $changed = $changed || ($protected != $consumer2->protected);
            $secret = fetch_POST('source_secret');
            $changed = $changed || ($secret != $consumer2->secret);
            $consumer2->name = $name;
            $consumer2->secret = $secret;
            $consumer2->enabled = $enabled;
            $consumer2->protected = $protected;
            $date = fetch_POST('source_from');
            if (empty($date)) {
                $changed = $changed || !is_null($consumer2->enableFrom);
                $consumer2->enableFrom = NULL;
            } else {
                $time = strtotime($date);
                $changed = $changed || is_null($consumer2->enableFrom) || $consumer2->enableFrom != $time;
                $consumer2->enableFrom = $time;
            }
            $date = fetch_POST('source_until');
            if (empty($date)) {
                $changed = $changed || !is_null($consumer2->enableUntil);
                $consumer2->enableUntil = NULL;
            } else {
                $time = strtotime($date);
                $changed = $changed || is_null($consumer2->enableUntil) || $consumer2->enableUntil != $time;
                $consumer2->enableUntil = $time;
            }
            $settings = $consumer2->getSettings();
            foreach ($settings as $name => $value) {
                if (strpos($name, 'custom_') !== 0) {
                    $consumer2->setSetting($name, NULL);
                    $changed = TRUE;
                }
            }
            $properties = fetch_POST('source_properties');
            $properties = str_replace("\r\n", "\n", $properties);
            $properties = explode("\n", $properties);
            foreach ($properties as $property) {
                if (strpos($property, '=') !== FALSE) {
                    list($name, $value) = explode('=', $property, 2);
                    if ($name) {
                        $consumer2->setSetting($name, $value);
                        $changed = TRUE;
                    }
                }
            }
            if (!$source || !$name || !$secret) {
                $sScreenMsg = "Please complete all fields";
            } else if ($changed) {
                $consumer2->save();
                $sScreenMsg = "The changes made for the source have been saved";
            } else {
                $sScreenMsg = "No changes were made to be saved";
            }
            break;
    }
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
  ?>
  <form action="edit.php" method="post" name="edit_source">
    <table class="option_list" style="width: 100%;">
      <tr>
        <td>
          <h2>Consumer Details</h2>
        </td>
      </tr>
      <tr>
        <td><label for="name">Name</label></td>
        <td>
          <input type="text" id="name" name="source_name" value="<?php echo $consumer2->name; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr>
        <td><label for="guid">Key</label></td>
        <td>
          <?php
          if ($source) {
              echo "      {$consumer2->getKey()}\n";
              echo "      <input type=\"hidden\" id=\"key\" name=\"source_key\" value=\"{$consumer2->getKey()}\">\n";
          } else {
              echo "      <input type=\"text\" id=\"key\" name=\"source_key\" value=\"{$consumer2->getKey()}\" size=\"40\" maxlength=\"255\">\n";
          }
          $from = '';
          if (!is_null($consumer2->enableFrom)) {
              $from = date('j-M-Y H:i', $consumer2->enableFrom);
          }
          $until = '';
          if (!is_null($consumer2->enableUntil)) {
              $until = date('j-M-Y H:i', $consumer2->enableUntil);
          }
          $properties = '';
          $settings = $consumer2->getSettings();
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
          <input type="text" id="secret" name="source_secret" value="<?php echo $consumer2->secret; ?>" size="50" maxlength="255">
        </td>
      </tr>
      <tr>
        <td><label for="protected">Protected?</label></td>
        <td>
          <input type="checkbox" id="protected" name="source_protected" value="1"<?php if ($consumer2->protected) echo ' checked="checked"'; ?>>
        </td>
      </tr>
      <tr>
        <td><label for="enabled">Enabled?</label></td>
        <td>
          <input type="checkbox" id="enabled" name="source_enabled" value="1"<?php if ($consumer2->enabled) echo ' checked="checked"'; ?>>
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
        <td>Created</td>
        <td><?php if (!empty($consumer2->created)) echo date("d-M-Y H:i", $consumer2->created); ?></td>
      </tr>
      <tr>
        <td>Last updated</td>
        <td><?php if (!empty($consumer2->updated)) echo date("d-M-Y H:i", $consumer2->updated); ?></td>
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
<?php
$UI->content_end();
?>