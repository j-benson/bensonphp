<?php
if (!defined("DS"))
{
    define("DS", DIRECTORY_SEPARATOR);
}
// The root of the public directory.
if (!defined("ROOT"))
{
    define("ROOT", dirname(__FILE__));
}

// The include path will be the directory '/Lib' classes should be put here.
set_include_path(implode(PATH_SEPARATOR, array(get_include_path(), 
                                            ROOT . DS . "src" . DS . "Lib")));


require "BensonPHP/Exceptions.php";
require "BensonPHP/Config.php";
require "BensonPHP/HtmlHelper.php";
require "BensonPHP/Form.php";
require "BensonPHP/Request.php";
require "BensonPHP/AbstractController.php";
require "BensonPHP/Controller.php";
require "BensonPHP/View.php";
require "BensonPHP/AbstractModel.php";
require "BensonPHP/DataAccess.php";
require "BensonPHP/Database.php";
require "BensonPHP/Session.php";
require "BensonPHP/Util.php";
require "BensonPHP/Access.php";
require "BensonPHP/Image.php";

// The development environment allows different settings in the config file to
// be applied. WARNING: Make sure this commented out in production.
Config::enableDevelopmentEnvironment();

Config::load();
if (Config::navigateTo("Site.ShowExceptions")->toString() === "true") {
    error_reporting(E_ALL);
} else {
    // Turn off error_reporting
    error_reporting(0);
}

Request::loadRoutes();
date_default_timezone_set(Config::navigateTo("Site.Timezone")->getAttribute("name"));

/**
 * Auto load the classes in the Models directory
 */
function __autoload($class) {
    $path = ROOT . DS . "src" . DS . "Models" . DS . $class . Config::getOption("Extension", "BensonPHP");
    if (file_exists($path)) {
        include_once($path);
        return;
    }
}
$controller = new Controller(Request::getRequestArgs());
$controller->dispatch();