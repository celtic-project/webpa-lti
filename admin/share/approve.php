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
###  Set approval status of a share
###

use ceLTIc\LTI\ResourceLink;

require_once('../../includes.php');

#
### Get query parameters
#
$do = lti_fetch_GET('do');
$resource_link_id = lti_fetch_GET('rlid');
#
### Check parameters
#
$ok = false;
$approve = null;
if (!empty($do) && !empty($resource_link_id)) {
    if ($do == 'Approve') {
        $approve = true;
    } else if ($do == 'Suspend') {
        $approve = false;
    }
    if (!is_null($approve)) {
#
### Update status
#
        $resource_link = ResourceLink::fromRecordId($resource_link_id, $lti_platform->getDataConnector());
        $resource_link->shareApproved = $approve;
        $ok = $resource_link->save();
    }
}
#
### Return HTTP error if request not processed
#
if (!$ok) {
    header("Status: 404 Not Found", true, 404);
}
?>