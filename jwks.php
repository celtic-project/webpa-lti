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
###  Generate the public key in JWKS format
###

use ceLTIc\LTI\Jwt\Jwt;

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/setting.php');

$jwt = Jwt::getJwtClient();
$keys = $jwt::getJWKS(LTI_PRIVATE_KEY, LTI_SIGNATURE_METHOD, LTI_KID);

header('Content-type: application/json');
echo json_encode($keys);
?>