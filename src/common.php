<?php
ini_set('display_errors', 1);
error_reporting(E_ALL );
date_default_timezone_set('UTC');
//php compatability
defined('JSON_PRETTY_PRINT') || define('JSON_PRETTY_PRINT', 0);

if(isset($_SERVER['APP_FS_ROOT'])){
    $APP_FS_ROOT = $_SERVER['APP_FS_ROOT'];
} else {
    $vendorPos = strripos(__DIR__, '/vendor');
    if(false !== $vendorPos){
        $APP_FS_ROOT = substr(__DIR__, 0, $vendorPos) . '/';
    }
}

if(!empty($APP_FS_ROOT)){
    if(file_exists($APP_FS_ROOT.'src/php/includes/common.php')){
        require_once $APP_FS_ROOT.'src/php/includes/common.php';
    }
    if (file_exists($APP_FS_ROOT.'src/php/includes/init.php')) {
        include_once $APP_FS_ROOT.'src/php/includes/init.php';
    }
}else{
    $APP_FS_ROOT = dirname(__DIR__) . '/';
    $APP_WEB_ROOT = dirname($_SERVER['SCRIPT_NAME']);
    if ($APP_WEB_ROOT[strlen($APP_WEB_ROOT)-1] != '/'){
        $APP_WEB_ROOT = $APP_WEB_ROOT . '/';
    }
    $APP_WEB_ROOT = str_replace("\\", "", $APP_WEB_ROOT);
    require_once $APP_FS_ROOT.'/vendor/autoload.php';
    $container = new Pimple\Container();
    $container['APP_FS_ROOT'] = $APP_FS_ROOT;
    $container['APP_WEB_ROOT'] = $APP_WEB_ROOT;
    $container['PUBLIC_ROOT'] = $APP_WEB_ROOT.'public';

    $baseinit = new esmeralda\base\Initializer();
    $container = $baseinit->initConf($container);
    $container = $baseinit->initBase($container);
    $container = $baseinit->initServices($container);
}

$apiinit = new esmeralda_api\Initializer();
$container = $apiinit->initWeb($container);
$container = $apiinit->initBase($container);
$container = $apiinit->initServices($container);
