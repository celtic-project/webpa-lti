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
###  Page to delete a source
###

use ceLTIc\LTI\Platform;

require_once('../../includes.php');

if (file_exists(DOC__ROOT . 'includes/classes/class_module.php')) {
    require_once(DOC__ROOT . 'includes/classes/class_module.php');
}
if (file_exists(DOC__ROOT . 'includes/classes/class_user.php')) {
    require_once(DOC__ROOT . 'includes/classes/class_user.php');
}

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
$source = lti_fetch_GET('s');
#
### Set the page information
#
$UI->page_title = APP__NAME . ' Delete source';
$UI->menu_selected = 'lti sources';
$UI->breadcrumbs = array('home' => '../', 'lti sources' => './', 'delete' => null,);
$UI->help_link = '?q=node/237';
#
### Delete modules for source
#
$modules = $CIS->get_user_modules(null, null, 'id', $source);
if ($modules) {
    foreach ($modules as $id => $module) {
        $mod = new Module('', '');
        $mod->module_id = $id;
        $mod->DAO = $DB;
        $mod->delete();
    }
}
#
### Delete users for source
#
$sql = 'SELECT u.user_id FROM ' .
    APP__DB_TABLE_PREFIX . "user u WHERE u.source_id = ?";
$stmt = lti_getConnection()->prepare($sql);
$stmt->bind_param('s', $source);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
if ($users) {
    foreach ($users as $ids) {
        $user = new User('', '');
        $user->id = $ids['user_id'];
        $user->DAO = $DB;
        $user->delete();
    }
}
#
### Delete source
#
$platform = Platform::fromConsumerKey($source, $dataconnector);
$sScreenMsg = "<p>The source has been deleted ($platform->name).</p>";
$platform->delete();
#
### Display page
#
$UI->head();
$UI->body();
$UI->content_start();
?>
<div class="content_box">
  <?php
  if (!empty($sScreenMsg)) {
      echo "  <div class=\"success_box\">{$sScreenMsg}</div>\n";
  }
  ?>
</div>
<?php
$UI->content_end();
?>