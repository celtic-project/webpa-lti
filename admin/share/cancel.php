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
###  Page to update list of enrolled users
###

use ceLTIc\LTI\ResourceLink;

require_once('../../includes.php');

#
### Get query parameters
#
$resource_link_id = lti_fetch_GET('rlid');
#
### Check parameters
#
if (!empty($resource_link_id)) {
#
### Initialise LTI Resource Link
#
    $resource_link = ResourceLink::fromRecordId($resource_link_id, $lti_platform->getDataConnector());
#
### Cancel share
#
    $resource_link->primaryResourceLinkId = null;
    $resource_link->shareApproved = null;
    $resource_link->save();
}
#
### Redirect to shares list page
#
header('Location: index.php');
?>