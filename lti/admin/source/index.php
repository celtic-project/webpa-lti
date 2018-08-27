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
 *    1.1.00  10-Feb-13  Changed "enabled?" column to "available?"
 *    1.2.00  27-Aug-18  Updated to include support for MySQLi
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
### Get list of sources
#
  $tool_provider = new LTI_Tool_Provider(NULL, array($DB->getConnection(), APP__DB_TABLE_PREFIX));
  $sources = $tool_provider->getConsumers();
#
### Set the page information
#
  $UI->page_title = APP__NAME . " view source data";
  $UI->menu_selected = 'lti sources';
  $UI->breadcrumbs = array ('home' => '../../../../admin/', 'lti sources'=>null);
  $UI->help_link = '?q=node/237';
  $heading = "Source Data";
#
### Display page
#
  $UI->head();
  $UI->body();
  $UI->content_start();
?>
<div class="content_box">
  <h2><?php echo $heading; ?></h2>
  <div class="obj">
    <table class="obj" cellpadding="2" cellspacing="2">
<?php
  if (count($sources) > 0) {
#
### Display table header row
#
    echo "    <tr>\n";
    echo "      <th class=\"icon\">&nbsp;</th>\n";
    echo "      <th>name</th>\n";
    echo "      <th>key</th>\n";
    echo "      <th>tool consumer</th>\n";
    echo "      <th>version</th>\n";
    echo "      <th>available?</th>\n";
    echo "      <th>last access</th>\n";
    echo "    </tr>\n";
#
### Display each source
#
    $i = 0;
    foreach ($sources as $source) {
      $i++;
      $key = $source->getKey();
      echo "    <tr>\n";
      echo '      <td class="icon">';
      echo '<a href="edit.php?s=' . urlencode($key) . '">';
      echo '<img src="../../../../images/buttons/edit.gif" width="16" height="16" alt="Edit ' . htmlentities($key) . ' source" title="Edit ' . htmlentities($key) . ' source" /></a>&nbsp;';
      if ($key != $_source_id) {
        echo '<a href="delete.php?s=' . urlencode($key) . '" onclick="return confirm(\'Delete ' . htmlentities($key) . ' source; are you sure?\');">';
        echo '<img src="../../../../images/buttons/cross.gif" width="16" height="16" alt="Delete ' . htmlentities($key) . ' source" title="Delete ' . htmlentities($key) . ' source" /></a></td>';
      } else {
        echo '<img src="../../../../images/buttons/blank.gif" width="16" height="16" alt="" />';
      }
      echo "</td>\n";
      echo "      <td class=\"obj_info_text\">{$source->name}</td>\n";
      echo "      <td class=\"obj_info_text\">{$key}</td>\n";
      echo '      <td class="obj_info_text">';
      if (!empty($source->consumer_guid)) {
        echo "<span title=\"{$source->consumer_guid}\">";
      }
      echo $source->consumer_name;
      if (!empty($source->consumer_guid)) {
        echo '</span>';
      }
      echo "</td>\n";
      echo '      <td class="obj_info_text">';
      if (!empty($source->lti_version)) {
        echo "<span title=\"{$source->lti_version}\">";
      }
      echo $source->consumer_version;
      if (!empty($source->lti_version)) {
        echo '</span>';
      }
      echo "</td>\n";
      $now = time();
      if ($source->getIsAvailable()) {
        $enabled = 'Yes';
      } else {
        $enabled = 'No';
      }
      echo "      <td class=\"obj_info_text\">{$enabled}</td>\n";
      if (is_null($source->last_access)) {
        $last = 'None';
      } else {
        $last = date('j-M-Y', $source->last_access);
      }
      echo "      <td class=\"obj_info_text\">{$last}</td>\n";
      echo "    </tr>\n";
    }
  } else {
    echo "    <tr><td>No sources have been defined</td></tr>\n";
  }
?>
    </table>
  </div>
  <a href="edit.php?s="><button type="button">Add new source</button></a>
</div>
<?php
  $UI->content_end();
?>