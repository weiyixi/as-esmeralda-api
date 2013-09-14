<?php

use lestore_product\app\model\ProductDetail;

use lestore_product\app\model\Product;

include_once __DIR__ . '/../modules/lestore_common.php';
use lestore_search\app\service\JsonSearchService;

$prefix = '/apis/search';

$container['search'] = new JsonSearchService();

$container['slim']->get("$prefix/:domain/weeklydeal", function($domain) use ($container){
	$language = 'en';
	$filename = $domain . '.weeklydeal.db.search.json';
	$container['search']->initJsonFile(__DIR__ . '/../modules/lestore_search/def/DB/' . $filename);

	$weeklyDealProducts = $container['search']->getWDProducts($domain);
	
	$container['slim']->render('common.tpl', array(
			'service' => $container['search'],
			'object' => $weeklyDealProducts,
			'nls' => array(),
			'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
			'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
	));
	
});

$container['slim']->get("$prefix/:domain/recommendation", function($domain) use ($container){
	echo 't';

});

$container['slim']->get("$prefix/:id", function($id) use ($container){
	echo 'test';
});
	
#$container['slim']->get("$prefix/:id/attrs", function($id) use ($container){
#    //get goods attributes 
#});
#
#$container['slim']->get("$prefix/:id/styles", function($id) use ($container){
#    //get goods styles 
#});
#
#$container['slim']->get("$prefix/:id/related", function($id) use ($container){
#    //get related goods 
#});
#
#$container['slim']->get("$prefix/:id/ultimate", function($id) use ($container){
#    //get ultimate buy
#});
#
#$container['slim']->get("$prefix/:id/recommendation", function($id) use ($container){
#    //get recommendation
#});

$container['slim']->run();
