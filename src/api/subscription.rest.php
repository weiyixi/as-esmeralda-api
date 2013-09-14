<?php

include_once __DIR__ . '/../modules/lestore_common.php';


$prefix = '/apis/subscription';
$container['subscription'] = function($c){
    //return new SubscriptionService();
};

$container['slim']->post("$prefix", function() use ($container){
    //subscribe
    echo 'subscribe';
});

$container['slim']->delete("$prefix/:email", function($email) use ($container){
    //unsubscribe
    echo $email;
});

$container['slim']->run();
