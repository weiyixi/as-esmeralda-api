<?php
include_once __DIR__ . '/../../common.php';
use esmeralda\base\Util;
use esmeralda\base\LogFactory;
use lestore\util\Helper;

$prefix = '/apis/user/:uid/cart';

function getSessionId($sid){
    if(empty($sid)){
        if (session_status() !== PHP_SESSION_ACTIVE) {
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
    return $uid;
    global $container;
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

function alert_back($msg, $url = '', $die = true)
{
	header('content-type: text/html; charset=utf-8');
	if (preg_match("/^-?\d+$/is", $url)) {
		$url = "history.go($url);";
	} else {
		$url = "location.href = " . ($url ? "'$url'" : "location.href") . ";";
	}
	$msg = str_replace("'", "\\'", $msg);
	echo "<script>alert('$msg');$url</script>
    <noscript>Your browser does not support client scripting, such as JavaScript! You must change that in order to visit our website.</noscript>";
	if ($die)
		die();
}

//{{{ GET: $prefix
$container['slim']->get($prefix, function($uid) use ($container){
    $app = $container['slim'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);

    $cartService = $container['user.cart'];
    //$domain = Util::conf('domain');
    $cart = $cartService->get($userId, $sessionId);

    $container['slim']->render('json.tpl', array(
        'value' => $cart,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}
//{{{ POST: $prefix (ADD TO CART)
$container['slim']->post("$prefix", function($uid) use ($container){
    $app = $container['slim'];
    $cartService = $container['user.cart'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);
    $contentType = $app->request->headers->get('Content-Type');
    
    // build new cart item
    $styles = $app->request->post('styles');
    $goodsId = $app->request->post('goods_id');
    $goodsNumber = $app->request->post('goods_number');
    $lang = $app->request->post('lang');
    if (!empty($styles['size_type'])) {
        $isCustomSize = $app->request->post('custom') == 'on';
        $styles['size_type'] = !$isCustomSize ? '_inch' : $styles['size_type'];
    }
    $product = $container['product']->getProduct($goodsId, $lang);
    $items = array(
        array(
            'id' => $goodsId,
            'number' => $goodsNumber,
            'styles' => $styles,
            'sn' => isset($product->goods_sn) ? $product->goods_sn : '',
            'name' => isset($product->name) ? $product->name : '',
            'marketPrice' => isset($product->market_price) ? $product->market_price : 0,
            'shopPrice' => isset($product->shop_price) ? $product->shop_price : 0,
        )
    );

    // add item to shopping cart
    $rs = $cartService->add($userId, $sessionId, $items);

    switch($contentType){
    case 'application/x-www-form-urlencoded':
        // redirect to backURL
        $backUrl = $app->request->get('back');
        $buy_now = $app->request->get('la');
        $referrer = $app->request->getReferrer();
        if (strtolower($buy_now) == 'buy_now') {
            $backUrl = '/checkout.php?act=checkout_payment_process';
        }
        if (empty($backUrl)) {
            $backUrl = $referrer;
        }
        foreach ($rs as $r) {
            if ($f == false) {
                // @FIXME strange action
                alert_back(Helper::nl('page_cart_add_to_shopping_cart_failed'), $referrer);
                break;
            }
        }
        // @todo delete wish list if necessary
        setCookie('JJGA', 'cart_' . $goodsId . '_' . $goodsNumber, time() + 3600, '/', Util::conf('cookie_domain'), false, true);
        header("Location: $backUrl");
        break;
    case 'application/json':
    default:
        // render result
        $container['slim']->render('json.tpl', array(
            'value' => $rs,
            'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
            'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
            'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
        ));
        break;
    }
});
//}}}
//{{{ POST: $prefix/:id (UPDATE CART ITEM)
$container['slim']->post("$prefix/:id", function($uid, $id) use ($container){
    $app = $container['slim'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);
    $contentType = $app->request->headers->get('Content-Type');
    $cartService = $container['user.cart'];
    $productService = $container['product'];

    $params = array();
    $params['styles'] = $app->request->post('styles');
    $params['number'] = $app->request->post('goods_number');
    $params['quantity'] = $app->request->post('quantity');
    $params['custom'] = $app->request->post('custom');
    // only consider changing product number
    $rs = $cartService->update($userId, $sessionId, $id, $params['number']);

    // construct require response result if send it back to website
    if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        $code = $rs ? 0 : 1;
        $r = array();
        $saved_all = 0;
        $total_amount = 0;
        $total_weight = 0;
        $total_item = 0;
        $cartProducts = $cartService->get($userId, $sessionId);
        foreach ($cartProducts as $key => $cartProduct) {
            $product = $productService->getProduct($cartProduct->goods_id);
            if ($cartProduct->rec_id == $id) {
                $r['shop_price'] = $product->shop_price;
                $r['total_price'] = $product->shop_price * $cartProduct->goods_number;
            }
            $total_weight += $product->goods_weight * $cartProduct->goods_number;
            $total_amount += $product->shop_price * $cartProduct->goods_number;
            $total_item += $cartProduct->goods_number;
            if ($product->off > 0) {
                $saved_all += $cartProduct->goods_number * ($product->market_price - $product->shop_price);
            }
        }
        $r['code'] = $code;
        $r['saved_all'] = $saved_all;
        $r['item_total'] = $total_item;
        $r['total_amount'] = $total_amount;
        $r['total_weight'] = $total_weight;
        $r['goods_gift_number'] = 0;
        $rs = $r;
    }

    $container['slim']->render('json.tpl', array(
        'value' => $rs,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}
//{{{ DELETE: $prefix(/:id)
$container['slim']->delete("$prefix(/:id)", function($uid, $id) use ($container){
    $app = $container['slim'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);
    $contentType = $app->request->getContentType();;

    $cartService = $container['user.cart'];
    $rs = $cartService->remove($userId, $sessionId, $id);

    switch($contentType){
    case 'application/x-www-form-urlencoded':
        $backUrl = $app->request->get('back');
        if (empty($backUrl)) {
            $backUrl = $app->request->getReferrer();
        }
        header("Location: $backUrl");
        break;
    case 'application/json':
    default:
        $container['slim']->render('json.tpl', array(
            'value' => $rs,
            'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
            'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
            'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
        ));
    }
});
//}}}

$container['slim']->run();

// @todo? add combined goods together to cart
/*
		if (isset($_POST['isTogether']) && $_POST['isTogether'] == 1 && isset($_POST['package_goods_ids']) && !empty($_POST['package_goods_ids']))
		{
			foreach ($_POST['package_goods_ids'] as $package_goods_id)
			{
				$package_styles = isset($_POST['package_styles'][$package_goods_id]) ? $_POST['package_styles'][$package_goods_id] : array();
				$package_is_custom_size = false;
				$package_goods_number = 1;
				$r = $Shopping->updateShoppingCart($package_goods_id, $package_goods_number, $package_styles, $package_is_custom_size, true);
			}
			//{{{ 不知道还有不有用，还是处理一下吧
            foreach ($_POST['is_together'] as $package_goods_id)
            {
                $package_goods_number = 1;
                // @FIXME 这个 cookie 还有用吗？
                setCookie('JJGA', 'cart_' . $package_goods_id . '_' . $package_goods_number, time() + 3600, '/', COOKIE_DOMAIN, false, true);
            }
		}
 *
 */
// @ todo? delete wish list
/*
			if ($_POST['is_wish_list']) {
				$rec_id = $_POST['rec_id'];
				$where = 'rec_id = ' . $rec_id;
				$r = $db->delete("wish_list", $where);
			}
*/
