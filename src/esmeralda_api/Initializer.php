<?php
namespace esmeralda_api;

use esmeralda_api\util\SlimWrapper;
#use esmeralda\category\JsonCategoryService;
use esmeralda\category\CategoryDao;
use esmeralda\category\DBCategoryService;
use esmeralda\shipping\ShippingDao;
use esmeralda\shipping\ShippingService;
use esmeralda\coupon\CouponDao;
use esmeralda\coupon\CouponService;
use esmeralda\style\StyleDao;
use esmeralda\style\StyleService;
use esmeralda\currency\CurrencyDao;
use esmeralda\currency\CurrencyService;
use \PDO;

class Initializer{

    public function initConf($container){
        include_once $container['APP_FS_ROOT'].'etc/env_config.php';
        $container['siteConf'] = $siteConf;
        return $container;
    }

    public function initDB($container){
        $container['db'] = $container->share(function($c){
            $siteConf = $c['siteConf'];
            try {
                $dbh = new PDO("mysql:host={$siteConf['db_host']};dbname={$siteConf['db_name']}", 
                    $siteConf['db_user'], $siteConf['db_pass']);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $dbh;
            } catch (PDOException $e) {
                //TODO
                //alert('Create database connection failed: ' . $e->getMessage());
                echo $e->getMessage();
                echo 'Sorry, our site is under maintenance. Please come back later!';
                die;
            }
        });
        return $container;
    }

    public function initWeb($container){
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
                realpath(dirname(__DIR__).'/view'),
            );
            $twig->twigOptions = array(
                'debug' => true,
                //     		'cache' => $c['APP_FS_ROOT'] . 'var/tpl/'
            );
            return $twig;
        });

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
        return $container;
    }

    public function initServices($container){
        $container['category'] = $container->share(function($c){
            //return new JsonCategoryService(
            //    $c['APP_FS_ROOT']. 'modules/esmeralda\category/def/DB/db.category.json');
            $dao = new CategoryDao($c['db']);
            return new DbCategoryService($dao);
        });

        #$container['shipping'] = $container->share(function($c){
        #    $dao = new ShippingDao($c['db']);
        #    return new ShippingService($dao);
        #});
        #
        #$container['coupon'] = $container->share(function($c){
        #    $dao = new CouponDao($c['db']);
        #    return $dao;
        #    //return new ShippingService($);
        #});
        #
        #$container['style'] = $container->share(function($c){
        #    $dao = new StyleDao($c['db']);
        #    return new StyleService($dao);
        #});
        #
        #$container['currency'] = $container->share(function($c){
        #    $dao = new CurrencyDao($c['db']);
        #    return new CurrencyService($dao);
        #});
        return $container;
    }
}
