<?php
include_once dirname(__DIR__) . '/common.php';

use esmeralda_service\base\ApiLogFactory;

$prefix = '/sync-apis/purchase';

$logger = ApiLogFactory::get('purchase.rest');

$container['slim']->post($prefix, function () use ($container, $logger) {
	$slim = $container['slim'];
	// code=>1 means that the error occurred (erp need)
	$response = array('code' => 1, 'msg' => '');
	$jsonTpl = 'json.tpl';
	$jsonFormat = JSON_FORCE_OBJECT | JSON_PRETTY_PRINT;

	$logger->info("Received purchase request. Request data: \n".print_r($_POST, true));

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
	$orderCopyInfo = $container['order']->getOrderCopyLog($_POST['order_sn']);
	if (!empty($orderCopyInfo)) {
		$response['code'] = 0;
		$response['msg'] = "order exists.";
		$logger->info($response['msg']);
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
	$response['code'] = 0;
	$response['msg'] = "success.";
	$logger->info($response['msg']);
	$slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
});

$container['slim']->run();
