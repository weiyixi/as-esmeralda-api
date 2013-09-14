<?php

include_once __DIR__ . '/../modules/lestore_common.php';

$prefix = '/apis/payment';
$container['payment'] = function($c){
    //return new PaymentService();
};

$container['slim']->get("$prefix/:id", function($id) use ($container){
    //get payment method
});

$container['slim']->run();
