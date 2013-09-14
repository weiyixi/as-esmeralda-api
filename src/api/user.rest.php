<?php

include_once __DIR__ . '/../modules/lestore_common.php';

$prefix = '/apis/user';
$container['user'] = function($c){
    //return new UserService();
};

$container['slim']->get("$prefix/:id", function($id) use ($container){
    //get user
});

$container['slim']->post("$prefix", function() use ($container){
    //create user
});

$container['slim']->post("$prefix/:id", function($id) use ($container){
    //update user
});

$container['slim']->get("$prefix/:id/address", function($id) use ($container){
    //get all user addresses
});

$container['slim']->get("$prefix/:id/address/:aid", function($id) use ($container){
    //get user address
});

$container['slim']->post("$prefix/:id/address", function($id) use ($container){
    //create user address
});

$container['slim']->post("$prefix/:id/address/:aid", function($id, $aid) use ($container){
    //update user address
});

$container['slim']->get("$prefix/:id/favorite", function($id) use ($container){
    //get all user favorite 
});

$container['slim']->post("$prefix/:id/favorite", function($id) use ($container){
    //create user favorite 
});

$container['slim']->delete("$prefix/:id/favorite/:fid", function($id, $fid) use ($container){
    //delete user favorite 
});

$container['slim']->get("$prefix/:id/history", function($id) use ($container){
    //get user history 
});

$container['slim']->get("$prefix/:id/cart", function($id) use ($container){
    //get user cart
});

$container['slim']->post("$prefix/:id/cart/:gid", function($id, $gid) use ($container){
    //add|update goods to user cart
});

$container['slim']->delete("$prefix/:id/cart/:gid", function($id, $gid) use ($container){
    //delete goods from user cart
});

$container['slim']->run();
