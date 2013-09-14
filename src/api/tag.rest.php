<?php

include_once __DIR__ . '/../modules/lestore_common.php';

$prefix = '/apis/tag';
$container['tag'] = function($c){
    //return new TagService();
};

$container['slim']->get("$prefix/group/:group", function($group) use ($container){
    //get user
});

$container['slim']->get("$prefix/name/:name", function($name) use ($container){
    //
});

$container['slim']->run();
