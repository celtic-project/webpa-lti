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

use ceLTIc\LTI\Tool;
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
### Get list of sources
#
$tool = new Tool($dataconnector);
$sources = $tool->getPlatforms();
#
### Set the page information
#
$UI->page_title = APP__NAME . " view source data";
$UI->menu_selected = 'lti sources';
$UI->breadcrumbs = array('home' => '../../../../admin/', 'lti sources' => null);
$UI->help_link = '?q=node/237';
$heading = "Source Data";
$url = APP__WWW . '/mod/' . LTI_MODULE_NAME . '/';
#
### Display page
#
$UI->head();
$UI->body();
$UI->content_start();
?>
<ul>
  <li><em>Launch URL, initiate login URL, redirection URI, registration URL:</em> <?php echo $url; ?>index.php</li>
  <li><em>Public keyset URL:</em> <?php echo $url; ?>jwks.php</li>
  <li><em>Canvas configuration URLs:</em> <?php echo $url; ?>configure.php (XML) and <?php echo $url; ?>configure.php?json (JSON)</li>
</ul>
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
          echo "      <th>platform</th>\n";
          echo "      <th>version</th>\n";
          echo "      <th>available?</th>\n";
          echo "      <th>protected?</th>\n";
          echo "      <th>debug?</th>\n";
          echo "      <th>last access</th>\n";
          echo "    </tr>\n";
#
### Display each source
#
          $i = 0;
          foreach ($sources as $source) {
              $i++;
              $key = $source->getKey();
              $name = $source->name;
              echo "    <tr>\n";
              echo '      <td class="icon">';
              echo '<a href="edit.php?s=' . urlencode($key) . '">';
              echo '<img src="../../../../images/buttons/edit.gif" width="16" height="16" alt="Edit &apos;' . htmlentities($name) . '&apos; source" title="Edit &apos;' . htmlentities($name) . '&apos; source" /></a>&nbsp;';
              if ($key != $_source_id) {
                  echo '<a href="delete.php?s=' . urlencode($key) . '" onclick="return confirm(\'Delete \\&apos;' . htmlentities($name) . '\\&apos; source; are you sure?\');">';
                  echo '<img src="../../../../images/buttons/cross.gif" width="16" height="16" alt="Delete &apos;' . htmlentities($name) . '&apos; source" title="Delete &apos;' . htmlentities($name) . '&apos; source" /></a></td>';
              } else {
                  echo '<img src="../../../../images/buttons/blank.gif" width="16" height="16" alt="" />';
              }
              echo "</td>\n";
              echo "      <td class=\"obj_info_text\">{$name}</td>\n";
              if ($source->ltiVersion !== Util::LTI_VERSION1P3) {
                  echo "      <td class=\"obj_info_text\">{$key}</td>\n";
              } else {
                  echo "      <td class=\"obj_info_text\">{$source->platformId}<br />{$source->clientId}<br />{$source->deploymentId}</td>\n";
              }
              echo '      <td class="obj_info_text">';
              if (!empty($source->consumerGuid)) {
                  echo "<span title=\"{$source->consumerGuid}\">";
              }
              echo $source->consumerName;
              if (!empty($source->consumerGuid)) {
                  echo '</span>';
              }
              echo "</td>\n";
              echo '      <td class="obj_info_text">';
              if (!empty($source->ltiVersion)) {
                  echo "<span title=\"{$source->ltiVersion}\">";
              }
              echo $source->consumerVersion;
              if (!empty($source->ltiVersion)) {
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
              if ($source->protected) {
                  $protected = 'Yes';
              } else {
                  $protected = 'No';
              }
              echo "      <td class=\"obj_info_text\">{$protected}</td>\n";
              if ($source->debugMode) {
                  $debugMode = 'Yes';
              } else {
                  $debugMode = 'No';
              }
              echo "      <td class=\"obj_info_text\">{$debugMode}</td>\n";
              if (is_null($source->lastAccess)) {
                  $last = 'None';
              } else {
                  $last = date('j-M-Y', $source->lastAccess);
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