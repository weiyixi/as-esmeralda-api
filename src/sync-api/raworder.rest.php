<?php
include_once dirname(__DIR__) . '/common.php';

use esmeralda_service\base\P11C;
use esmeralda_service\base\ApiLogFactory;
use esmeralda_service\base\Util;

$prefix = '/sync-apis/raworder';

$logger = ApiLogFactory::get('raworder.rest');

$container['slim']->post($prefix.'/post/:domain', function ($domain) use ($container, $logger) {

	$slim = $container['slim'];
	$orderService = $container['order'];
	$orderWService = $container['orderW'];
	$response = array('code' => 0, 'msg' => '', 'data' => array());
	$jsonTpl = 'json.tpl';
	$jsonFormat = JSON_FORCE_OBJECT | JSON_PRETTY_PRINT;

    $postOrder = !empty($_POST['order']) ? json_decode($_POST['order'], true) : $_POST;
	if (!isset($postOrder['order_info']) || !is_array($postOrder['order_info'])) {
		$response['msg'] = "missing order info.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}
	if (!isset($postOrder['order_goods']) || !is_array($postOrder['order_goods'])) {
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
		if (!$orderService->checkOrderSnExists($orderSn)) {
			break;
		}
	} while ($try < 10);
	$postOrder['order_info']['order_sn'] = $orderSn;

	// insert goods sku items
	$skuIds = array();
	if (isset($postOrder['goods_sku']) && is_array($postOrder['goods_sku'])) {
		$orderWService->beginTransaction();
		foreach ($postOrder['goods_sku'] as $oldRecId=>$skuItems) {
			$skuId = $orderService->checkSkuIdExists($skuItems);
			if (!$skuId) {
				if (!$orderWService->insert('goods_sku', $skuItems)) {
					$orderWService->rollBack();
					$response['msg'] = "insert goods sku failed.";
					$logger->error($response['msg']);
				    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
					die;
				}
				$skuId = $orderWService->getLastInsertId();
			}
			$skuIds[$oldRecId] = $skuId;
		}
		$res = $orderWService->commit();
		if (!$res) {
			$response['msg'] = "insert goods sku transaction commit failed.";
			$logger->error($response['msg']);
		    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
			die;
		}
	}

	// create sku
	$skus = array();
	foreach ($postOrder['order_goods'] as $oldRecId=>$orderGoods) {
		$sku = $orderGoods['sku'];
//		$sku = preg_replace('/(\d*)(g\d+)([a-fh-z]?)/', '\1g'.$orderGoods['goods_id'].'\3', $sku);
		if (isset($skuIds[$oldRecId])) {
			$sku = preg_replace('/(\d*)(z\d+)([a-y]?)/', '\1z'.$skuIds[$oldRecId].'\3', $sku);
		}
		$skus[$oldRecId] = $sku;
	}

	// insert goods style
	$goodsStyleIds = array();
	if (isset($postOrder['goods_style']) && is_array($postOrder['goods_style'])) {
		$orderWService->beginTransaction();
		foreach ($postOrder['goods_style'] as $oldRecId => $styleItems) {
			// sku can not be empty
			if (!isset($skus[$oldRecId]) || empty($skus[$oldRecId])) {
				continue;
			}
			// replace old skuId with new skuId
			$styleItems['sku_id'] = isset($skuIds[$oldRecId]) ? $skuIds[$oldRecId] : 0;
			// replace old sku with new sku
			$styleItems['sku'] = $skus[$oldRecId];
			$gStyleId = $orderService->checkGStyleIdExists($styleItems);
			if (!$gStyleId) {
				$styleItems['style_price'] = $postOrder['order_goods'][$oldRecId]['shop_price'];
				if (!$orderWService->insert('goods_style', $styleItems)) {
					$orderWService->rollBack();
					$response['msg'] = "insert goods style failed.";
					$logger->error($response['msg']);
				    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
					die;
				}
				$gStyleId = $orderWService->getLastInsertId();
			}
			$goodsStyleIds[$oldRecId] = $gStyleId;
		}
		$res = $orderWService->commit();
		if (!$res) {
			$response['msg'] = "insert goods style transaction commit failed.";
			$logger->error($response['msg']);
		    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
			die;
		}
	}

	// insert order_info
	$orderWService->beginTransaction();
	$affectedRows = $orderWService->insert('order_info', $postOrder['order_info']);
	if (!$affectedRows) {
		$response['msg'] = "insert order info failed.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
		die;
	}
	$orderId = $orderWService->getLastInsertId();
	// insert order_goods
	$recIds = array();
	foreach ($postOrder['order_goods'] as $oldRecId=>$orderGoods) {
		$orderGoods['order_id'] = $orderId;
		$orderGoods['goods_style_id'] = $goodsStyleIds[$oldRecId];
		$orderGoods['sku'] = $skus[$oldRecId];
		$orderGoods['sku_id'] = isset($skuIds[$oldRecId]) ? $skuIds[$oldRecId] : 0;
		$affectedRows = $orderWService->insert('order_goods', $orderGoods);
		if (!$affectedRows) {
			$orderWService->rollBack();
			$response['msg'] = "insert order goods failed.";
			$logger->error($response['msg']);
		    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
			die;
		}
		$newRecId = $orderWService->getLastInsertId();
		$recIds[$oldRecId] = $newRecId;
	}
	// insert order_extension
	if (isset($postOrder['order_extension']) && !empty($postOrder['order_extension'])) {
		array_push($postOrder['order_extension'], array(
			'ext_name' => 'newRecIds',
			'ext_value' => json_encode($recIds),
			'order_id' => $orderId,
		));
		$postOrder['order_extension'] = array_map(function(&$row) use ($orderId){$row['order_id'] = $orderId; return $row;}, $postOrder['order_extension']);
		$affectedRows = $orderWService->insert('order_extension', $postOrder['order_extension']);
		if (!$affectedRows) {
			$orderWService->rollBack();
			$response['msg'] = "insert order extension failed.";
			$logger->error($response['msg']);
		    $slim->render($jsonTpl, array('value' => $response, 'json_format' => $jsonFormat));
			die;
		}
	}
	$res = $orderWService->commit();
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

$container['slim']->post($prefix.'/pay/:orderSn', function ($orderSn) use ($container, $logger) {

	$slim = $container['slim'];
	$orderService = $container['order'];
	$orderWService = $container['orderW'];
	$response = array('code' => 0, 'msg' => '', 'data' => array());
	$jsonTpl = 'json.tpl';
	$paid = 2;

	if (empty($orderSn)) {
		$response['msg'] = "missing order sn.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
		die;
	}
	if (!$orderService->checkOrderSnExists($orderSn)) {
		$response['msg'] = "order not exists.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
		die;
	}

	$update = array('pay_status'=>$paid);
	$query['where'] = "order_sn = '{$orderSn}'";
	$affectedRows = $orderWService->update('order_info', $update, $query);
	if ($affectedRows === false) {
		$response['msg'] = "update pay status failed.";
		$logger->error($response['msg']);
	    $slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
		die;
	}

	$response['code'] = 1;
	$response['msg'] = "success.";
	$slim->render($jsonTpl, array('value' => $response, 'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT));
});

$container['slim']->run();
