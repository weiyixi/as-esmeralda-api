<?php
include_once __DIR__ . '/../../common.php';

$prefix = '/apis/user/:uid/order';

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
}

function getUserId($uid){
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
    $userId = getUserId($uid);

    $orderService = $container['user.order'];
    //$domain = Util::conf('domain');
    $orderList = $orderService->getOrders($userId);

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
    $userId = getUserId($uid);
    $contentType = $app->request->headers->get('Content-Type');
    switch($contentType){
    case 'application/x-www-form-urlencoded':
        $order = $app->request->post('styles');
        break;
    case 'application/json':
    default:
        $body = $app->request->getBody();
        $body= <<<JSON
{
"addressId":1,
"shipId":1,
"couponCode":"",
"importantDay":"2014-07-10",
"order_track_id":"d6f8826c13800cfa1751d750618cad9f",
"livechatinc":{
        "goal_id":"1111",
        "visitor_id":"S1403679406.1203f75fc3"
    },
"payment_id":157,
"postscript":""
}
JSON;
        $order = json_decode($body, true);
        break;
    }

    $addrService = $container['user.address'];
    $address = $addrService->get($userId, $order->addressId);

    $shipService = $container['shipping'];
    $shipment = $shipService->get($order->shipId);

    $payService = $container['payment'];
    $payment = $payService->get($order->paymentId);

    $sessionId = getSessionId(null);
    $cartService = $container['user.cart'];
    $cart = $cartService->get($userId, $sessionId);

    $orderService = $container['user.order'];
    //$domain = Util::conf('domain');

    /*
     * Parse address info. If not saved, save it.
     */

    $rs = $orderService->create($userId, $orderInfo);

    $container['slim']->render('json.tpl', array(
        'value' => $rs,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}
//{{{ POST: $prefix/:sn
$container['slim']->post("$prefix/:sn", function($uid, $orderSn) use ($container){
    $app = $container['slim'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);
    $contentType = $app->request->headers->get('Content-Type');
    switch($contentType){
    case 'application/x-www-form-urlencoded':
        $params = array();
        $params['styles'] = $app->request->post('styles');
        $params['number'] = $app->request->post('number');
        $params['quantity'] = $app->request->post('quantity');
        $params['custom'] = $app->request->post('custom');
        break;
    case 'application/json':
    default:
        $params = json_decode($app->request->getBody());
        break;
    }

    $cartService = $container['user.cart'];
    //$domain = Util::conf('domain');
    $rs = $cartService->update($userId, $sessionId, $id, $params);

    $container['slim']->render('json.tpl', array(
        'value' => $rs,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}

$container['slim']->run();
