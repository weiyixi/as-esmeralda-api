<?php
include_once __DIR__ . '/../common.php';
use lestore\cart\shipping\ShippingProcessor;

$prefix = '/apis/shipment';

function getSessionId($sid){
    if(empty($sid)){
        if (function_exists('ssession_status') && session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $sessionId = session_id();
    }else if($sid == 'all'){
        $sessionId = null;
    }else{
        $sessionId = $sid;
    }
    return $sessionId;
}

function getUserId($uid){
    global $container;
    if (function_exists('ssession_status') && session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $userService = $container['user'];
    $userId = 0;
    if($uid == 'self'){
        if(isset($_SESSION['user_id'])){
            $userId = $_SESSION['user_id'];
        }
    }else{
        $user = $userService->getUserByUID($uid);
        $userId = $user->user_id;
    }
    return $userId;
}

$container['slim']->get("$prefix/:id", function($id) use ($container){
    //get shipment method
});

$container['slim']->get("$prefix/user/:uid", function($uid) use ($container){
    $app = $container['slim'];
    $cartService = $container['user.cart'];
    $productService = $container['product'];
    $lang = $app->request->get('lang');
    $currencyName = $app->request->get('currencyName');
    $countryId = $app->request->get('country_id');
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);

    $cartProducts = $cartService->get($userId, $sessionId);
    $goods_in_cart = array();
    foreach ($cartProducts as $key => $cartProduct) {
        $product = $productService->getProduct($cartProduct->goods_id);
        $goods_in_cart[$key]['rec_id'] = $cartProduct->rec_id;
        $goods_in_cart[$key]['goods_id'] = $cartProduct->goods_id;
        $goods_in_cart[$key]['cat_id'] = $product->cat_id;
        $goods_in_cart[$key]['goods_number'] = $cartProduct->goods_number;
        $goods_in_cart[$key]['styles'] = json_decode($cartProduct->styles, true);
        $goods_in_cart[$key]['shop_price'] = $product->shop_price;
        $goods_in_cart[$key]['market_price'] = $product->market_price;
        $goods_in_cart[$key]['cat_id'] = $product->cat_id;
    }

    $currency = $container['currency']->getCurrencyByName($currencyName);
    $shippingInfo = ShippingProcessor::getShippingInfo($goods_in_cart, $lang, $currency, $countryId);

    $container['slim']->render('json.tpl', array(
        'value' => $shippingInfo,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
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
