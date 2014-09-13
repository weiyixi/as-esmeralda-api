<?php
include_once __DIR__ . '/../common.php';

$prefix = '/apis/region';

//{{{ GET: $prefix/id/:id
$container['slim']->get("$prefix/id/:id", function($id) use ($container){
    $slim = $container['slim'];
    $region = $container['region']->getRegion($id);
    $slim->render('json.tpl', array(
        'value' => $region,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
    ));
});
//}}}
//{{{ GET: $prefix/countries
$container['slim']->get("$prefix/countries", function() use ($container){
    $slim = $container['slim'];
    $countries = $container['region']->getAllCountry();
    $slim->render('json.tpl', array(
        'value' => $countries,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
    ));
});
//}}}
$container['slim']->run();

