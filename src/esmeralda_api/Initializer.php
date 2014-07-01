<?php namespace esmeralda_api;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SwiftMailerHandler;
use esmeralda_service\base\BaseEditDao;
use esmeralda_service\order\OrderDao;
use esmeralda_service\order\DbOrderWService;
use esmeralda_service\order\DBOrderService;

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

    public function initBase($container){
        $container['api_log_handlers'] = $container->share(function($c){
            $siteConf = $c['siteConf'];
            if(!isset($siteConf['api_log_dir'])){
                $siteConf['api_log_dir'] = '/var/log/esmeralda-api';
            }
            Initializer::ensureDir($siteConf['api_log_dir']);
            $transport = \Swift_AWSTransport::newInstance($siteConf['AWS_ACCESS_KEY_ID'], $siteConf['AWS_SECRET_ACCESS_KEY']);
            $message = \Swift_Message::newInstance('esmeralda api occurs some problem.')->setFrom(array($siteConf['NOTICE_EMAIL']))->setTo(array($siteConf['ALARM_EMAIL']));
            $handers = array(
                new StreamHandler($siteConf['log_dir'].'/esmeralda-api-'.date('Y-m-d').'.log', $siteConf['log_level']),
                new SwiftMailerHandler(\Swift_Mailer::newInstance($transport), $message, Logger::CRITICAL),
            );
            return $handers;
        });

        return $container;
    }

    public function initServices($container){
        $container['order'] = $container->share(function($c){
            $siteConf = $c['siteConf'];
            $dao = new OrderDao($c);
            return new DBOrderService($dao, $siteConf['domain']);
        });
        $container['orderW'] = $container->share(function ($c) {
            $siteConf = $c['siteConf'];
            $editDao = new BaseEditDao($c);
            return new DbOrderWService($editDao, $siteConf['domain']);
        });

        return $container;
    }

    public static function ensureDir($dir){
        if(!is_dir($dir)){
            @mkdir($dir, 0755, true);
        }
    }
}
