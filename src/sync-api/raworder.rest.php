<?php
include_once dirname(__DIR__) . '/common.php';

use esmeralda_service\base\P11C;
use esmeralda_service\base\Util;

$prefix = '/sync-apis/raworder';

$logger = ApiLogFactory::get('raworder.rest');

$container['slim']->post($prefix.'/post/:domain', function ($domain) use ($container) {

	$response = array('code' => 0, 'msg' => '', 'data' => array());
	$jsonTpl = 'json.tpl';
	$jsonFormat = JSON_FORCE_OBJECT | JSON_PRETTY_PRINT;

	if (!isset($_POST['order_info']) || !is_array($_POST['order_info'])) {
		$response['msg'] = "missing order info.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}
	if (!isset($_POST['order_goods']) || !is_array($_POST['order_goods'])) {
		$response['msg'] = "missing goods info.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}

	// create order sn
	$proCode = P11C::proCode($domain);
	if (strtolower($domain) != 'jjshouse' && empty($proCode)) {
		$response['msg'] = "create order sn failed.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}
	$try = 0;
	do {
		$try++;
		$orderSn = $proCode.Util::gen_order_sn();
		if (!$container['order']->checkOrderSnExists($orderSn)) {
			break;
		}
	} while ($try < 10);
	$_POST['order_info']['order_sn'] = $orderSn;

	// insert goods sku
	$skuIds = array();
	if (isset($_POST['goods_sku']) && is_array($_POST['goods_sku'])) {
		$container['orderW']->beginTransaction();
		foreach ($_POST['goods_sku'] as $oldSkuId=>$skuItems) {
			$skuId = $container['order']->checkSkuIdExists($skuItems);
			if (!$skuId) {
				$skuId = $container['orderW']->insert('goods_sku', $skuItems);
				if (!$skuId) {
					$container['orderW']->rollBack();
					$response['msg'] = "insert goods sku failed.";
					$logger->error($response['msg']);
				    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
					die;
				}
			}
			$skuIds[$oldSkuId] = $skuId;
		}
		$res = $container['orderW']->commit();
		if (!$res) {
			$response['msg'] = "insert goods sku transaction commit failed.";
			$logger->error($response['msg']);
		    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
			die;
		}
	}

	// insert goods style
	$goodsStyleIds = array();
	if (isset($_POST['goods_style']) && is_array($_POST['goods_style']) && !empty($skuIds)) {
		$container['orderW']->beginTransaction();
		foreach ($_POST['goods_style'] as $oldGStyleId => $styleItems) {
			if (!isset($styleItems['sku_id']) || !isset($skuIds[$styleItems['sku_id']])) {
				continue;
			}
			// replace old skuId with new skuId
			$styleItems['sku_id'] = $skuIds[$styleItems['sku_id']];
			// create new sku
			$styleItems['sku'] = preg_replace('/(\d*)(z\d+)([a-y]?)/', '\1z'.$styleItems['sku_id'].'\3', $styleItems['sku']);
			$styleItems['sku'] = preg_replace('/(\d*)(g\d+)([a-fh-z]?)/', '\1g'.$styleItems['goods_id'].'\3', $styleItems['sku']);
			$gStyleId = $container['order']->checkGStyleIdExists($styleItems);
			if (!$gStyleId) {
				$gStyleId = $container['orderW']->insert('goods_style', $styleItems);
				if (!$gStyleId) {
					$container['orderW']->rollBack();
					$response['msg'] = "insert goods style failed.";
					$logger->error($response['msg']);
				    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
					die;
				}
			}
			$goodsStyleIds[$oldGStyleId] = $gStyleId;
		}
		$res = $container['orderW']->commit();
		if (!$res) {
			$response['msg'] = "insert goods style transaction commit failed.";
			$logger->error($response['msg']);
		    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
			die;
		}
	}

	// insert goods style id
	$container['orderW']->beginTransaction();
	$affectedRows = $container['orderW']->insert('order_info', array($_POST['order_info']));
	if (!$affectedRows) {
		$response['msg'] = "insert order info failed.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}
	$affectedRows = $container['orderW']->insert('order_goods', $_POST['order_goods']);
	if (!$affectedRows) {
		$container['orderW']->rollBack();
		$response['msg'] = "insert goods info failed.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}
	if (isset($_POST['order_extension']) && !empty($_POST['order_extension'])) {
		$affectedRows = $container['orderW']->insert('order_extension', array($_POST['order_extension']));
		if (!$affectedRows) {
			$container['orderW']->rollBack();
			$response['msg'] = "insert order extension failed.";
			$logger->error($response['msg']);
		    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
			die;
		}
	}
	$res = $container['orderW']->commit();
	if (!$res) {
		$response['msg'] = "insert transaction commit failed.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}

	// success return
	$response['code'] = 1;
	$response['msg'] = "success.";
	$response['data']['orderSn'] = $orderSn;
	$slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
});

$container['slim']->post($prefix.'/pay', function () use ($container) {

	$response = array('code' => 0, 'msg' => '', 'data' => array());
	$jsonTpl = 'json.tpl';
	$paid = 2;
	$orderSn = $_POST['orderSn'];

	if (!isset($orderSn)) {
		$response['msg'] = "missing order sn.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
		die;
	}
	if (!$container['order']->checkOrderSnExists($orderSn)) {
		$response['msg'] = "order not exists.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
		die;
	}

	$update = array('pay_status'=>$paid);
	$query['where'] = 'order_sn = '.$orderSn;
	$affectedRows = $container['order']->update('order_info', $update, $query);
	if ($affectedRows === false) {
		$response['msg'] = "sql execute failed.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
		die;
	}

	$response['code'] = 1;
	$response['msg'] = "success.";
	$slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
});

$container['slim']->run();
