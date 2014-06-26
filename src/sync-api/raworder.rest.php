<?php
include_once dirname(__DIR__) . '/common.php';

$prefix = '/sync-apis/raworder';

$container['slim']->put($prefix.'/post', function () {
    echo 'raworder post';
});

$container['slim']->put($prefix.'/pay', function () {
    echo 'raworder pay';
});

$container['slim']->run();
