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

/*
	// ajax
	case 'shippingFee':
		$current_currency_id = SiteConfig::getCurrencyId();
		$currency_info = SiteConfig::getCurrencyInfo($current_currency_id);

		$goods_in_cart = $Shopping->getAllGoodsFromShoppingCart();
		$goods_amount = 0;
		foreach ($goods_in_cart as $v) {
			$goods_amount += $v['shop_price'] * $v['goods_number'];
		}

		$shipping_method_id = isset($_REQUEST['shipping_method_id']) ? intval($_REQUEST['shipping_method_id']) : 0;

		if ($shipping_method_id) {
			$getShipingMethodById = $Shopping->getShipingMethodById($shipping_method_id);

		} else {
			$r = array(
				'code' => 1,
				'msg' => 'Error shipping method.'
			);
		}

		break;
 */
