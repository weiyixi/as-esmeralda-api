<?php
include_once __DIR__ . '/../../common.php';
use esmeralda\base\Util;
use esmeralda\base\LogFactory;

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
    $cartService = $container['user.cart'];

    // only consider changing product number
    $params = array();
    $params['number'] = $app->request->post('goods_number');
    $rs = $cartService->update($userId, $sessionId, $id, $params['number']);

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
