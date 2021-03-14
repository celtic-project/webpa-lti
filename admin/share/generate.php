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
###  Generate a new share key
###

use ceLTIc\LTI\ResourceLink;
use ceLTIc\LTI\ResourceLinkShareKey;

require_once('../../includes.php');

require_once('../../setting.php');

#
### Get query parameters
#
$life = lti_fetch_GET('life', '1');
$param = lti_fetch_GET('auto_approve');
#
### Initialise LTI Resource Link
#
$resource_link = ResourceLink::fromPlatform($lti_platform, $_module_code);
#
### Generate new key value
#
$length = $resource_link->getSetting('custom_share_key_length');
if (!$length) {
    $length = SHARE_KEY_LENGTH;
}
$auto_approve = !empty($param);
$share_key = new ResourceLinkShareKey($resource_link);
$share_key->length = $length;
$share_key->life = $life;
$share_key->autoApprove = $auto_approve;
$share_key->save();
$id = $share_key->getId();
#
### Return key value
#
echo $id;
?>