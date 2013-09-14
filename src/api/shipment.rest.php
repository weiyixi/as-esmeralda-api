<?php

include_once __DIR__ . '/../modules/lestore_common.php';

$prefix = '/apis/shipment';
$container['shipment'] = function($c){
    //return new ShipmentService();
};

$container['slim']->get("$prefix/:id", function($id) use ($container){
    //get shipment method
});

$container['slim']->run();
