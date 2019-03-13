<?php
/*
 *  webpa-lti - WebPA module to add LTI support
 *  Copyright (C) 2019  Stephen P Vickers
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
###  Define constants for options
###

define('LTI_MODULE_NAME', 'lti');
define('ALLOW_SHARING', TRUE);
define('SHARE_KEY_LENGTH', 10);
define('DEFAULT_EMAIL', '');

###
###  Set API handlers for services
###

use ceLTIc\LTI\ApiHook\ApiHook;
use ceLTIc\LTI\ToolProvider;
use ceLTIc\LTI\ResourceLink;

ToolProvider::registerApiHook(ApiHook::$USER_ID_HOOK, 'canvas', 'ceLTIc\LTI\ApiHook\canvas\CanvasApiToolProvider');
ResourceLink::registerApiHook(ApiHook::$MEMBERSHIPS_SERVICE_HOOK, 'canvas', 'ceLTIc\LTI\ApiHook\canvas\CanvasApiResourceLink');
ResourceLink::registerApiHook(ApiHook::$MEMBERSHIPS_SERVICE_HOOK, 'moodle', 'ceLTIc\LTI\ApiHook\moodle\MoodleApiResourceLink');
?>