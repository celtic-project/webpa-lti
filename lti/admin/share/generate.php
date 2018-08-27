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
 *    1.1.00  10-Feb-13  Updated for LTI_Tool_Provider 2.3 class
*/

###
###  Generate a new share key
###

  require_once('../../../../includes/inc_global.php');

  require_once('../../lib/LTI_Tool_Provider.php');
  require_once('../../setting.php');

#
### Get query parameters
#
  $life = mysql_real_escape_string(fetch_GET('life', 1));
  $param = mysql_real_escape_string(fetch_GET('auto_approve'));
#
### Initialise LTI Resource Link
#
  $consumer = new LTI_Tool_Consumer($_source_id, APP__DB_TABLE_PREFIX);
  $resource_link = new LTI_Resource_Link($consumer, $_module_code);
#
### Generate new key value
#
  $length = $resource_link->getSetting('custom_share_key_length');
  if (!$length) {
    $length = SHARE_KEY_LENGTH;
  }
  $auto_approve = !empty($param);
  $share_key = new LTI_Resource_Link_Share_Key($resource_link);
  $share_key->length = $length;
  $share_key->life = $life;
  $share_key->auto_approve = $auto_approve;
  $share_key->save();
  $id = $share_key->getId();
#
### Return key value
#
  echo $id;

?>