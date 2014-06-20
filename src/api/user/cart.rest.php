<?php
include_once __DIR__ . '/../../common.php';

use esmeralda\user\cart\validator\StyleValidator;

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

$container['slim']->get($prefix, function($uid) use ($container){
    $app = $container['slim'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);

    $cartService = $container['user/cart'];
    //$domain = Util::conf('domain');
    $cart = $cartService->get($userId, $sessionId);

    $container['slim']->render('json.tpl', array(
        'value' => $cart,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});

$container['slim']->post("$prefix", function($uid) use ($container){
    $app = $container['slim'];
    $sessionId = getSessionId($app->request->get('sid'));
    $userId = getUserId($uid);
    $contentType = $app->request->headers->get('Content-Type');
    switch($contentType){
    case 'application/x-www-form-urlencoded':
        $items = $app->request->post('styles');
        break;
    case 'application/json':
    default:
        $body = $app->request->getBody();
        $items = json_decode($body, true);
        break;
    }

    $cartService = $container['user/cart'];
    //$domain = Util::conf('domain');
    $rs = $cartService->add($userId, $sessionId, $items);

    $container['slim']->render('json.tpl', array(
        'value' => $rs,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});

$container['slim']->post("$prefix/:id", function($uid, $id) use ($container){
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

    $cartService = $container['user/cart'];
    //$domain = Util::conf('domain');
    $rs = $cartService->update($userId, $sessionId, $id, $params);

    $container['slim']->render('json.tpl', array(
        'value' => $rs,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});

$container['slim']->delete("$prefix(/:id)", function($id) use ($container){
    //remove OR empty
});


$container['slim']->run();
