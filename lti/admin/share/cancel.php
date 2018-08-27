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
###  Page to update list of enrolled users
###

  require_once('../../../../includes/inc_global.php');

#
### Get query parameters
#
  $consumer_key = fetch_GET('ci');
  $resource_link_id = fetch_GET('rlid');
#
### Check parameters
#
  if (!empty($consumer_key) && !empty($resource_link_id)) {
#
### Initialise LTI Resource Link
#
    $consumer = new LTI_Tool_Consumer($consumer_key, APP__DB_TABLE_PREFIX);
    $resource_link = new LTI_Resource_Link($consumer, $resource_link_id);
#
### Cancel share
#
    $resource_link->primary_consumer_key = NULL;
    $resource_link->primary_resource_link_id = NULL;
    $resource_link->share_approved = NULL;
    $resource_link->save();
  }
#
### Redirect to shares list page
#
  header('Location: index.php');

?>