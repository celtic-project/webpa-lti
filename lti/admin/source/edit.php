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
 *    1.1.00  10-Feb-13
*/

###
###  Page to allow details of a source to be edited
###

 require_once("../../../../includes/inc_global.php");

#
### Option only available for administrators
#
 if (!check_user($_user, APP__USER_TYPE_ADMIN)){
  header('Location:'. APP__WWW .'/logout.php?msg=denied');
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
  $consumer = new LTI_Tool_Consumer($source, APP__DB_TABLE_PREFIX);
#
### Set the page information
#
  $UI->menu_selected = 'lti sources';
  if (empty($source)) {
    $UI->page_title = APP__NAME . ' Add source system';
    $UI->breadcrumbs = array ('home' => '../../../../admin','lti sources'=>'./','add'=>null, );
  } else {
    $UI->page_title = APP__NAME . ' Edit source system';
    $UI->breadcrumbs = array ('home' => '../../../../admin','lti sources'=>'./','edit'=>null, );
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
        $changed = ($name != $consumer->name);
        $enabled = (fetch_POST('source_enabled') == '1');
        $changed = $changed || ($enabled != $consumer->enabled);
        $secret = fetch_POST('source_secret');
        $changed = $changed || ($secret != $consumer->secret);
        $consumer->name = $name;
        $consumer->secret = $secret;
        $consumer->protected = TRUE;
        $consumer->enabled = $enabled;
        $date = fetch_POST('source_from');
        if (empty($date)) {
          $changed = $changed || !is_null($consumer->enable_from);
          $consumer->enable_from = NULL;
        } else {
          $time = strtotime($date);
          $changed = $changed || is_null($consumer->enable_from) || $consumer->enable_from != $time;
          $consumer->enable_from = $time;
        }
        $date = fetch_POST('source_until');
        if (empty($date)) {
          $changed = $changed || !is_null($consumer->enable_until);
          $consumer->enable_until = NULL;
        } else {
          $time = strtotime($date);
          $changed = $changed || is_null($consumer->enable_until) || $consumer->enable_until != $time;
          $consumer->enable_until = $time;
        }
        if (!$source || !$name || !$secret) {
          $sScreenMsg = "Please complete all fields";
        } else if ($changed) {
          $consumer->save();
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
  if(!empty($sScreenMsg)){
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
        <input type="text" id="name" name="source_name" value="<?php echo $consumer->name; ?>" size="50" maxlength="255">
      </td>
    </tr>
    <tr>
      <td><label for="guid">Key</label></td>
      <td>
<?php
  if ($source) {
    echo "      {$consumer->getKey()}\n";
    echo "      <input type=\"hidden\" id=\"key\" name=\"source_key\" value=\"{$consumer->getKey()}\">\n";
  } else {
    echo "      <input type=\"text\" id=\"key\" name=\"source_key\" value=\"{$consumer->getKey()}\" size=\"40\" maxlength=\"255\">\n";
  }
  $from = '';
  if (!is_null($consumer->enable_from)) {
    $from = date('j-M-Y H:i', $consumer->enable_from);
  }
  $until = '';
  if (!is_null($consumer->enable_until)) {
    $until = date('j-M-Y H:i', $consumer->enable_until);
  }
?>
      </td>
    </tr>
    <tr>
      <td><label for="secret">Secret</label></td>
      <td>
        <input type="text" id="secret" name="source_secret" value="<?php echo $consumer->secret; ?>" size="50" maxlength="255">
      </td>
    </tr>
    <tr>
      <td><label for="enabled">Enabled</label></td>
      <td>
        <input type="checkbox" id="enabled" name="source_enabled" value="1"<?php if ($consumer->enabled) echo ' checked="checked"'; ?>>
      </td>
    </tr>
    <tr>
      <td><label for="from">Enable from</label></td>
      <td>
        <input type="text" id="from" name="source_from" value="<?php echo $from; ?>" size="20">
      </td>
    </tr>
    <tr>
      <td><label for="until">Enable until</label></td>
      <td>
        <input type="text" id="until" name="source_until" value="<?php echo $until; ?>" size="20">
      </td>
    </tr>
    <tr>
      <td>Created</td>
      <td><?php if (!empty($consumer->created)) echo date("d-M-Y H:i", $consumer->created); ?></td>
    </tr>
    <tr>
      <td>Updated</td>
      <td><?php if (!empty($consumer->updated)) echo date("d-M-Y H:i", $consumer->updated); ?></td>
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