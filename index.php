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
###  Process an LTI launch request
###

use ceLTIc\LTI\DataConnector\DataConnector;

require_once('includes.php');

if (file_exists(DOC__ROOT . 'includes/classes/class_authenticator.php')) {
    require_once(DOC__ROOT . 'includes/classes/class_authenticator.php');
} else {

    class Authenticator extends \WebPA\includes\classes\Authenticator
    {

        function __construct($username = NULL, $password = NULL)
        {
            global $CIS;

            parent::__construct($CIS, $username, $password);
        }

    }

}

require_once('includes.php');

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/setting.php');

#
### Cancel any existing session
#
$_SESSION = array();
session_destroy();
session_start();

#
### Open database
#
if (method_exists($DB, 'open')) {
    $DB->open();
}

#
### Perform LTI connection
#
$dataconnector = DataConnector::getDataConnector(lti_getConnection(), APP__DB_TABLE_PREFIX);
$tool = new WebPA_Tool($dataconnector);
$tool->handleRequest();

#
### Close database
#
if (method_exists($DB, 'close')) {
    $DB->close();
}

exit;
?>