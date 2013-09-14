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

require_once $APP_FS_ROOT . '/etc/env_config.php';
$container['siteConf'] = $siteConf;

$container['db'] = $container->share(function($c){
    $siteConf = $c['siteConf'];
    try {
        $dbh = new \PDO("mysql:host={$siteConf['db_host']};dbname={$siteConf['db_name']}", 
            $siteConf['db_user'], $siteConf['db_pass']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    } catch (\PDOException $e) {
        //TODO
        //alert('Create database connection failed: ' . $e->getMessage());
       	echo $e->getMessage();
        echo 'Sorry, our site is under maintenance. Please come back later!';
        die;
    }
});

$container['logger'] = $container->share(function($c){
    return new \Flynsarmy\SlimMonolog\Log\MonologWriter(array(
        'handlers' => array(
            new \Monolog\Handler\StreamHandler($c['APP_FS_ROOT'].'var/log/'.date('Y-m-d').'.log'),
        ),
    ));
});

$container['tplengine'] = $container->share(function($c){
    $twig = new \Slim\Views\Twig();
    $twig->twigTemplateDirs = array(
        realpath($c['APP_FS_ROOT'].'src/view'),
    );
    $twig->twigOptions = array(
     		'debug' => true,
//     		'cache' => $c['APP_FS_ROOT'] . 'var/tpl/'
    );
    return $twig;
});

use esmeralda_api\util\SlimWrapper;
$container['slim'] = $container->share(function($c){
    \Slim\Route::setDefaultConditions(array(
        'lang' => '[a-z]{2}'
    ));
    return new SlimWrapper(array(
    //return new \Slim\Slim(array(
        'mode' => 'development',
        'log.writer' => $c['logger'],
        'log.enabled' => true,
        'log.level' => \Slim\Log::DEBUG,
        'debug' => true,
        'view' => $c['tplengine'],
    ));
});

#use esmeralda\category\JsonCategoryService;
use esmeralda\category\CategoryDao;
use esmeralda\category\DBCategoryService;
$container['category'] = $container->share(function($c){
    //return new JsonCategoryService(
    //    $c['APP_FS_ROOT']. 'modules/esmeralda\category/def/DB/db.category.json');
    $dao = new CategoryDao($c['db']);
    return new DbCategoryService($dao);
});

#use esmeralda\shipping\ShippingDao;
#use esmeralda\shipping\ShippingService;
#$container['shipping'] = $container->share(function($c){
#    $dao = new ShippingDao($c['db']);
#    return new ShippingService($dao);
#});
#
#use esmeralda\coupon\CouponDao;
#use esmeralda\coupon\CouponService;
#$container['coupon'] = $container->share(function($c){
#    $dao = new CouponDao($c['db']);
#    return $dao;
#    //return new ShippingService($);
#});
#
#use esmeralda\style\StyleDao;
#use esmeralda\style\StyleService;
#$container['style'] = $container->share(function($c){
#    $dao = new StyleDao($c['db']);
#    return new StyleService($dao);
#});
#
#use esmeralda\currency\CurrencyDao;
#use esmeralda\currency\CurrencyService;
#$container['currency'] = $container->share(function($c){
#    $dao = new CurrencyDao($c['db']);
#    return new CurrencyService($dao);
#});
#
