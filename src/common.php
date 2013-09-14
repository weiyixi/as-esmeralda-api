<?php
ini_set('display_errors', 1);
error_reporting(E_ALL );
date_default_timezone_set('UTC');

$APP_FS_ROOT = dirname(__DIR__) . '/';
$APP_WEB_ROOT = dirname($_SERVER['SCRIPT_NAME']);
if ($APP_WEB_ROOT[strlen($APP_WEB_ROOT)-1] != '/'){
	$APP_WEB_ROOT = $APP_WEB_ROOT . '/';
}
$APP_WEB_ROOT = str_replace("\\", "", $APP_WEB_ROOT);

require_once $APP_FS_ROOT.'vendor/autoload.php';
require_once $APP_FS_ROOT.'src/autoloader.php';
$container = new Pimple();
$container['APP_FS_ROOT'] = $APP_FS_ROOT;
$container['APP_WEB_ROOT'] = $APP_WEB_ROOT;
$container['PUBLIC_ROOT'] = $APP_WEB_ROOT.'public';

use esmeralda_api\Initializer;
$init = new Initializer();
$container = $init->initConf($container);
$container = $init->initDB($container);
$container = $init->initWeb($container);
$container = $init->initServices($container);
