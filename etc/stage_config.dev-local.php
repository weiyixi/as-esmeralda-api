<?php
$siteConf = array_merge($siteConf, array(
    'node' => 'dev-local',
    'db_host' => "localhost:3306",
    'db_name' => "jjshouse",
    'db_user' => "jjshouse",
    'db_pass' => "jjshouse",

    'log_level' => \Monolog\Logger::DEBUG,

    //'fs_repo' => $templates_dir.'/repo',
    //'cache_cluster' => isset($_v5_['mc.cluster']) ? $_v5_['mc.cluster'] : array(
    //    array($cache_host_2, 11211),
    //),
    'cache_timeout' => 60,//seconds

    'rs_server' => isset($rs_server) ? $rs_server : '/',

    'twig.debug' => true,
    'twig.strict' => false,
    'twig.cache' => false,

    'slim.log.level' => \Slim\Log::DEBUG,
    'slim.debug' => true,
    'slim.mode' => 'development',
));
