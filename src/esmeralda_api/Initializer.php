<?php
namespace esmeralda_api;

use esmeralda_api\util\SlimWrapper;

class Initializer{

    protected function tplPath(){
        return array(
            realpath(dirname(__DIR__).'/view'),
        );
    }

    public function initWeb($container){
        $container['tplengine'] = $container->share(function($c){
            $twig = new \Slim\Views\Twig();
            $twig->twigTemplateDirs = $this->tplPath();
            $twig->parserOptions = array(
                'debug' => false,
                'cache' => $c['APP_FS_ROOT'] . 'var/tpl/'
            );
            return $twig;
        });

        $container['slim_logger'] = $container->share(function($c){
            return new \Flynsarmy\SlimMonolog\Log\MonologWriter(array(
                'handlers' => array(
                    new \Monolog\Handler\StreamHandler($c['APP_FS_ROOT'].'var/log/'.date('Y-m-d').'.log'),
                ),
            ));
        });

        $container['slim'] = $container->share(function($c){
            \Slim\Route::setDefaultConditions(array(
                'lang' => '[a-z]{2}'
            ));
            return new SlimWrapper(array(
                //return new \Slim\Slim(array(
                'mode' => 'development',
                'log.writer' => $c['slim_logger'],
                'log.enabled' => true,
                'log.level' => \Slim\Log::DEBUG,
                'debug' => true,
                'view' => $c['tplengine'],
            ));
        });
        return $container;
    }
}
