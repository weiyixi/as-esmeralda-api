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
}
