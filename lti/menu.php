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
 *    1.1.00  10-Feb-13  Renamed "upload data" option to "sync data"
 *                       Added option to override name of "logout" option
 *    1.2.00  27-Aug-18  Updated to include support for MySQLi
*/

###
###  Update WebPA menu when accessing an LTI module
###

  require_once('setting.php');
  require_once('lib/LTI_Tool_Provider.php');

#
### Check if this is an LTI connection
#
  if ($_source_id) {
    $DB->open();
    $consumer = new LTI_Tool_Consumer($_SESSION['_user_source_id'], array($DB->getConnection(), APP__DB_TABLE_PREFIX));
    $user_resource_link = new LTI_Resource_Link($consumer, $_SESSION['_user_context_id']);
    if ($this->_user->is_staff() && $_source_id) {
#
### Update upload option if Memberships service is available
#
      $menu = $this->get_menu('Admin');
      if ($user_resource_link->hasMembershipsService()) {
        $menu['sync data'] = APP__WWW . "/mod/$mod/admin/manage/";
//      } else {
//        unset($menu['upload data']);
      }
      unset($menu['upload data']);
#
### Add upload option if Outcomes service is available
#
      if ($user_resource_link->hasOutcomesService()) {
        $menu['transfer grades'] = APP__WWW . "/mod/$mod/admin/grade/";
      }
#
### Add sharing option if enabled
#
      if (ALLOW_SHARING && ($_source_id == $_SESSION['_user_source_id'])) {
        $menu['sharing'] = APP__WWW . "/mod/$mod/admin/share/";
      }
      $this->set_menu('Admin', $menu);
    }
  }

#
### Add sources menu for administrators
#
  if ($this->_user->is_admin()) {
    $this->set_menu('LTI Admin', array('lti sources' => APP__WWW . "/mod/$mod/admin/source/",
                                 'change source' => APP__WWW . "/mod/$mod/admin/source.php"));
  } else {
#
### Add message to logout option
#
    $menu = $this->get_menu(' ');
    if (isset($_SESSION['logout_url'])) {
      $text = 'return to VLE';
      if (isset($_SESSION['branding_return_menu_text'])) {
        $text = $_SESSION['branding_return_menu_text'];
      }
      $menu[$text] = APP__WWW .'/logout.php';
      unset($menu['logout']);
    } else {
      $menu['logout'] = APP__WWW .'/logout.php?lti_msg=' . urlencode('You have been logged out of ' . APP__NAME);
    }
    $this->set_menu(' ', $menu);
  }

?>