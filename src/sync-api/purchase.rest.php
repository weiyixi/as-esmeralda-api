<?php
include_once dirname(__DIR__) . '/common.php';

use esmeralda_service\base\ApiLogFactory;

$prefix = '/sync-apis/purchase';

$logger = ApiLogFactory::get('purchase.rest');

$container['slim']->post($prefix, function () use ($container, $logger) {
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

	$affectedRows = $container['orderW']->insert('order_copy_log', array(
		'order_sn' => $_POST['order_sn'],
		'data' => json_encode($_POST),
	));
	if (!$affectedRows) {
		$response['msg'] = "save data failed.";
		$logger->critical($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}

	// success return
	$response['code'] = 1;
	$response['msg'] = "success.";
	$slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
});

$container['slim']->run();
