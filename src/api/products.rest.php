<?php
include_once __DIR__ . '/../common.php';

$prefix = '/apis/products/:domain';

function parseIds($param_ids){
    $ids = array();
    if (strstr($param_ids, ':')){
		$ids = explode(":", $ids);
		$start = $ids[0];
		$len = $ids[1];
        $ids = range($start, $start + $len);
	} else if (strstr($param_ids, '-')) {
		$ids = explode("-", $ids);
		$start = $ids[0];
		$end = $ids[1];
        $ids = range($start, $end);
	} else {
		$ids = explode(",", $param_ids);
	}
    return $ids;
}

$container['slim']->get("$prefix/:ids", function($domain, $ids) use ($container){
    $ids = parseIds($ids);
    $slim = $container['slim'];
    $status = $slim->request->params('status');
    if(null == $status){
        $status = 'active';
    }
    switch($status){
    case 'any':
        $products = $container['product']->getProducts($ids, null, -1, -1, -1);
        break;
    case 'active':
    default:
        $products = $container['product']->getProducts($ids, null);
        break;
    }
    $slim->render('json.tpl', array(
        'value' => $products,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
$container['slim']->run();
