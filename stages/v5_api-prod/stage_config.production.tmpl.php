<?php
use Monolog\Logger;
global $APP_FS_ROOT;

$siteConf = array_merge($siteConf, array(
    'db_host' => "__DB_HOST__",
    'db_name' => "__DB_NAME__",
    'db_user' => "__DB_USER__",
    'db_pass' => "__DB_PASS__",
    'db_conf' => array(
        // db name
        'JJsHouse' => array(
            'db_host' => "__DB_HOST__",
            'db_name' => "__DB_NAME__",
            'db_user' => "__DB_USER__",
            'db_pass' => "__DB_PASS__",
        ),
    ),

    'login' => array('user' => 'lebbay', 'pwd' => 'passw0rd'),

    // root path of display image files
//    'disImgRoots' => array(
//        'JJsHouse' => 'http://v5editor.dhvalue.com/upload',
//        'JenJenHouse' => 'http://v5editor.dhvalue.com/upload',
//        'DressFirst' => 'http://v5editor.dhvalue.com/upload',
//    ),
    'disImgRoots' => array(
        'JJsHouse' => 'http://d1jfn47pte6pdy.cloudfront.net/v5res',
        'Azazie' => 'http://d1jfn47pte6pdy.cloudfront.net/v5res',
    ),

    'log_level' => Logger::ERROR,
    'log_dir' => $APP_FS_ROOT.'/var/log',
    'fs_repo' => $APP_FS_ROOT.'/var/repo',

    'cache_cluster' =>  array(
        array('__CACHE_HOST_1__', 11211),
        array('__CACHE_HOST_2__', 11211),
    ),
    'cache_timeout' => 60,//seconds

    //'rs_server' => isset($rs_server) ? $rs_server : '/',

    'twig.debug' => false,
    'twig.strict' => false,
    'twig.cache' => $APP_FS_ROOT.'/var/twig',

    'slim.log.level' => \Slim\Log::ERROR,
    'slim.debug' => false,
    'slim.mode' => 'production',
));
