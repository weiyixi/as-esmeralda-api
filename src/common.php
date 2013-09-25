<?php
if(isset($_SERVER['APP_FS_ROOT'])){ 
    $APP_FS_ROOT = $_SERVER['APP_FS_ROOT'].'/';
}
if(empty($APP_FS_ROOT)){
    $APP_FS_ROOT = dirname(__DIR__) . '/';
}

$APP_WEB_ROOT = dirname($_SERVER['SCRIPT_NAME']);
if ($APP_WEB_ROOT[strlen($APP_WEB_ROOT)-1] != '/'){
	$APP_WEB_ROOT = $APP_WEB_ROOT . '/';
}
$APP_WEB_ROOT = str_replace("\\", "", $APP_WEB_ROOT);

$vendor_load = realpath(__DIR__.'/../../../autoload.php');
if($vendor_load && file_exists($vendor_load)){
    require_once $vendor_load;
}else{
    require_once $APP_FS_ROOT.'vendor/autoload.php';
}
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
