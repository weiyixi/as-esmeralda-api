<?php
include_once dirname(__DIR__) . '/common.php';

use esmeralda_service\base\ApiLogFactory;

$logger = ApiLogFactory::get('purchase.rest');
$logger->error('hello world.');

$prefix = '/sync-apis/purchase';

$container['slim']->post($prefix, function () use ($container) {
	$slim = $container['slim'];
	$response = array('code' => 0, 'msg' => '');
	$jsonTpl = 'json.tpl';
	$jsonFormat = JSON_FORCE_OBJECT | JSON_PRETTY_PRINT;

	if (!isset($_POST['order_sn']) || empty($_POST['order_sn'])) {
		$response['msg'] = "missing order sn.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}
	if (!isset($_POST['goods']) || !is_array($_POST['goods']) || empty($_POST['goods'])) {
		$response['msg'] = "missing goods info.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}


});

$container['slim']->run();
