<?php
include_once dirname(__DIR__) . '/common.php';

$prefix = '/sync-apis/purchase';

$container['slim']->put($prefix, function () {
    echo 'haha';
});

$container['slim']->run();
