<?php
include_once __DIR__ . '/../../common.php';
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
//{{{ POST: $prefix
$container['slim']->post("$prefix", function($uid) use ($container){
    $app = $container['slim'];
    $cartService = $container['user.cart'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);
    $contentType = $app->request->headers->get('Content-Type');
    
    switch($contentType){
    case 'application/x-www-form-urlencoded':
        $backUrl = $app->request->get('back');
        if (empty($backUrl)) {
            $backUrl = $app->request->getReferrer();
        }
        // build cart item
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
        // redirect to backURL
        header("Location: $backUrl");
        break;
    case 'application/json':
    default:
        $body = $app->request->getBody();
        $items = json_decode($body, true);
        //$domain = Util::conf('domain');
        $rs = $cartService->add($userId, $sessionId, $items);
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
//{{{ POST: $prefix/:id
$container['slim']->post("$prefix/:id", function($uid, $id) use ($container){
    $app = $container['slim'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);
    $contentType = $app->request->headers->get('Content-Type');
    $cartService = $container['user.cart'];
    $productService = $container['product'];
    if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        $params = array();
        $params['styles'] = $app->request->post('styles');
        $params['number'] = $app->request->post('goods_number');
        $params['quantity'] = $app->request->post('quantity');
        $params['custom'] = $app->request->post('custom');
        // only consider changing product number
        $rs = $cartService->update($userId, $sessionId, $id, $params['number']);
        $code = $rs ? 0 : 1;
        $r = array();
        $saved_all = 0;
        $total_amount = 0;
        $total_weight = 0;
        $cartProducts = $cartService->get($userId, $sessionId);
        foreach ($cartProducts as $key => $cartProduct) {
            $product = $productService->getProduct($cartProduct->goods_id);
            if ($cartProduct->rec_id == $id) {
                $r['shop_price'] = $product->shop_price;
                $r['total_price'] = $product->shop_price * $cartProduct->goods_number;
            }
            $total_weight += $product->goods_weight * $cartProduct->goods_number;
            $total_amount += $product->shop_price * $cartProduct->goods_number;
            if ($product->off > 0) {
                $saved_all += $cartProduct->goods_number * ($product->market_price - $product->shop_price);
            }
        }
        $r['code'] = $code;
        $r['saved_all'] = $saved_all;
        $r['item_total'] = count($cartProducts);
        $r['total_amount'] = $total_amount;
        $r['total_weight'] = $total_weight;
        $r['goods_gift_number'] = 0;
        echo json_encode($r);die;
    }

    switch($contentType){
    case 'application/x-www-form-urlencoded':
        break;
    case 'application/json':
    default:
        $params = json_decode($app->request->getBody());
        $rs = $cartService->update($userId, $sessionId, $id, $params['number']);
        break;
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
    //remove OR empty
    $backUrl = $app->request->get('back');
    if (empty($backUrl)) {
        $backUrl = $app->request->getReferrer();
    }

    $cartService = $container['user.cart'];
    $rs = $cartService->remove($userId, $sessionId, $id);

    header("Location: $backUrl");
    //$container['slim']->render('json.tpl', array(
    //    'value' => $rs,
    //    'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
    //    'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
    //    'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    //));
});
//}}}

$container['slim']->run();
