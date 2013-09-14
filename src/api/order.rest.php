<?php

include_once __DIR__ . '/../modules/lestore_common.php';

use lestore_order\app\dao\OrderDao;
use lestore_order\app\service\OrderService;

$prefix = '/apis/order';
$container['order'] = $container->share(function($c){
    $dao = new OrderDao($c['db']);
    return new lestore_order\app\service\OrderService($dao);
});

$container['slim']->get("$prefix/:sn", function($sn) use ($container){
    $container['slim']->render('order.tpl', array(
        'order'=> $container['order']->getOrder($sn),
    ));
});

$container['slim']->post("$prefix", function() use ($container){
    //create order
    $request = $app->request();
    $body = $request->getBody();

});

$container['slim']->post("$prefix/:sn", function($sn) use ($container){
    //update order
});

$container['slim']->get("$prefix/:sn/address/:addrType", function($sn, $addrType) use ($container){ 
    //get order address
});

$container['slim']->post("$prefix/:sn/address/:addrType", function($sn, $addrType) use ($container){
    //create|update order addresses
});

$container['slim']->get("$prefix/:sn/goods/:orderGoodsId", function($sn, $orderGoodsId) use ($container){
    //add|update order goods
});

$container['slim']->post("$prefix/:sn/goods", function($sn) use ($container){
    //create order goods
});

$container['slim']->post("$prefix/:sn/goods/:orderGoodsId", function($sn, $orderGoodsId) use ($container){
    //update order goods
});

$container['slim']->delete("$prefix/:sn/goods/:orderGoodsId", function($sn, $orderGoodsId) use ($container){
    //delete order goods
});

$container['slim']->get("$prefix/:sn/shipment", function($sn) use ($container){
    //get shipment type
});

$container['slim']->post("$prefix/:sn/shipment", function($sn) use ($container){
    //create|update shipment type
});

$container['slim']->get("$prefix/:sn/payment", function($sn) use ($container){
    //get payment method
});
$container['slim']->post("$prefix/:sn/payment", function($sn) use ($container){
    //create|update payment method
});

$container['slim']->get("$prefix/:sn/charge", function($sn) use ($container){
    //get payment method
});

$container['slim']->get("$prefix/:sn/charge/ship", function($sn) use ($container){
    //get payment method
});

$container['slim']->run();
