<?php
require_once(dirname(__FILE__) . '/../../includes/inc_global.php');

function lti_getConnection()
{
    global $DB;

    $connection = $DB->getConnection();
    if (method_exists($connection, 'getWrappedConnection')) {
        $connection = $connection->getWrappedConnection()->getWrappedResourceHandle();
    }

    return $connection;
}

if (!function_exists('check_user')) {

    function check_user($_user, $user_type = NULL)
    {
        return \WebPA\includes\functions\Common::check_user($_user, $user_type);
    }

}

if (!function_exists('fetch_GET')) {

    function fetch_GET($key, $default_value = '')
    {
        return \WebPA\includes\functions\Common::fetch_GET($key, $default_value);
    }

}

function lti_fetch_GET($key, $default_value = '')
{
    $value = fetch_GET($key, $default_value);
    if (!empty($value)) {
        $value = mysqli_real_escape_string(lti_getConnection(), $value);
    }

    return $value;
}

if (!function_exists('fetch_POST')) {

    function fetch_POST($key, $default_value = '')
    {
        return \WebPA\includes\functions\Common::fetch_POST($key, $default_value);
    }

}

function lti_fetch_POST($key, $default_value = '')
{
    $value = fetch_POST($key, $default_value);
    if (!empty($value)) {
        $value = mysqli_real_escape_string(lti_getConnection(), $value);
    }

    return $value;
}

if (!function_exists('logEvent')) {

    function logEvent($description, $module_id = NULL, $object_id = NULL)
    {
        global $DB;

        return \WebPA\includes\functions\Common::logEvent($DB, $description, $module_id, $object_id);
    }

}

if (!class_exists('User')) {

    class User extends \WebPA\includes\classes\User
    {

    }

}

if (!class_exists('Module')) {

    class Module extends \WebPA\includes\classes\Module
    {

    }

}

if (!class_exists('GroupHandler')) {

    class GroupHandler extends \WebPA\includes\classes\GroupHandler
    {

    }

}
?>
