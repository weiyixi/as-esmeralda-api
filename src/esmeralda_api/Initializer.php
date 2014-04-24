<?php namespace esmeralda_api;

class SlimWrapper extends \Slim\Slim{
    public function __construct($options){
        parent::__construct($options);
    }

    public function render($tpl, $params = array(), $status = NULL){
        $req = $this->request();
        $accept = $req->headers('Accept');

        $twig = $this->view->getInstance();
        $loader = $twig->getLoader();
		if (false !== stripos($accept, 'htm') && $loader->exists($tpl.".htm")){
            parent::render($tpl.".htm", $params);
            $resp = $this->response();
            $resp['Content-Type'] = 'text/html';
            return;
        }
		if (false !== stripos($accept, 'json') && $loader->exists($tpl.".json")){
            parent::render($tpl.".json", $params);
            $resp = $this->response();
            $resp['Content-Type'] = 'application/json';
            return;
        }
        parent::render($tpl.".json", $params);
        $resp = $this->response();
        $resp['Content-Type'] = 'application/json';
        return;
    }
}

class Initializer{
    public function tplPath(){
        $paths[] = realpath(dirname(__DIR__).'/view');
        return $paths;
    }

    public function initWeb($container){
        $t = $this;
        $container['tplengine'] = $container->share(function($c) use ($t){
            $siteConf = $c['siteConf'];
            $twig = new \Slim\Views\Twig();
            $twig->twigTemplateDirs = $t->tplPath();
            $twig->parserOptions = array(
                'debug' => isset($siteConf['twig.debug']) ? $siteConf['twig.debug'] : false,
                'strict_variables' => isset($siteConf['twig.strict']) ? $siteConf['twig.strict'] : false,
                'cache' => isset($siteConf['twig.cache']) ? $siteConf['twig.cache'] : false,
            );
            $twig->parserExtensions = array(
                new \Twig_Extension_Debug(),
            );
            return $twig;
        });

        $container['slim_logger'] = $container->share(function($c){
            return new \Flynsarmy\SlimMonolog\Log\MonologWriter(array(
                'handlers' => $c['log_handlers'],
            ));
        });

        $container['slim'] = $container->share(function($c){
            $siteConf = $c['siteConf'];
            \Slim\Route::setDefaultConditions(array(
                'lang' => '[a-z]{2}'
            ));
            return new SlimWrapper(array(
                'mode' => isset($siteConf['slim.mode']) ? $siteConf['slim.mode'] : 'production',
                'log.writer' => $c['slim_logger'],
                'log.enabled' => true,
                'log.level' => isset($siteConf['slim.log.level']) ? $siteConf['slim.log.level'] : \Slim\Log::ERROR,
                'debug' => isset($siteConf['slim.debug']) ? $siteConf['slim.debug'] : false,
                'view' => $c['tplengine'],
            ));
        });
        return $container;
    }
}
