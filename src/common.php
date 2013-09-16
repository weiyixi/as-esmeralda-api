<?php
ini_set('display_errors', 1);
error_reporting(E_ALL );
date_default_timezone_set('UTC');

if(isset($_SERVER['APP_FS_ROOT'])){ 
    $APP_FS_ROOT = $_SERVER['APP_FS_ROOT'];
}
if(empty($APP_FS_ROOT)){
    $APP_FS_ROOT = dirname(__DIR__) . '/';
}

$APP_WEB_ROOT = dirname($_SERVER['SCRIPT_NAME']);
if ($APP_WEB_ROOT[strlen($APP_WEB_ROOT)-1] != '/'){
	$APP_WEB_ROOT = $APP_WEB_ROOT . '/';
}
$APP_WEB_ROOT = str_replace("\\", "", $APP_WEB_ROOT);

require_once $APP_FS_ROOT.'vendor/autoload.php';
$container = new Pimple();
$container['APP_FS_ROOT'] = $APP_FS_ROOT;
$container['APP_WEB_ROOT'] = $APP_WEB_ROOT;
$container['PUBLIC_ROOT'] = $APP_WEB_ROOT.'public';

$baseinit = new esmeralda\base\Initializer();
$container = $baseinit->initConf($container);
$container = $baseinit->initBase($container);
$container = $baseinit->initServices($container);

$apiinit = new esmeralda_api\Initializer();
$container = $apiinit->initWeb($container);
