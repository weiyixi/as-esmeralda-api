<?php

include_once __DIR__ . '/../modules/lestore_common.php';
use lestore_cart\app\service\DbCartService;
use lestore_base\app\utils\ECS;
use lestore_base\app\utils\ECMysql;

$prefix = '/apis/cart';

$container['cart'] = $container->share(function($c){
    return new DbCartService($c['db']);
});


session_start();

$container['slim']->get("$prefix/:userId", function($userId) use ($container){
	$cart = $container['cart']->getCartProduct($userId);
	
    $container['slim']->render('common.tpl', array(
        'object' => $cart,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
    
});

$container['slim']->delete("$prefix/:userId/:recId", function($userId, $recId) use ($container){
	$result = $container['cart']->deleteCartProduct($userId, $recId);
	
	if (1 == $result){
		echo 'delete success.';
	} elseif (0 == $result){
		echo 'there is no such record with id=' . $recId;
	} else {
		echo 'delete failed.';
	}

});


$container['slim']->run();
