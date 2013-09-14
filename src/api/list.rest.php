<?php

include_once __DIR__ . '/../modules/lestore_common.php';

use lestore_product_list\app\service\ProductListServiceFactory;
use lestore_product_list\app\dao\ProductListDao;
use lestore_product_list\app\service\DBDomainProductListService;

$prefix = '/apis/list';
const DEFAULT_PAGE_SIZE = 24;

$container['list'] = $container->share(function($c){
	$dao = new ProductListDao($c['db']);
	return new DBDomainProductListService($dao);
});

$container['slim']->get("$prefix/:domain(/:offset(/:size))", 
	function($domain, $offset=0, $size=DEFAULT_PAGE_SIZE) use ($container){
	$productIds = $container['list']->getProductIds($domain, $offset, $size);
	
	$container['slim']->render('product_list.tpl', array(
			'productIds' => $productIds,
			'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
			'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
	));

});

$container['slim']->get("$prefix/tag/:tag(/:offset(/:size))", 
    function($tag, $offset=0, $size=DEFAULT_PAGE_SIZE) use ($container){
    //get tag list
    }
);

$container['slim']->get("$prefix/weekly_deal(/:offset(/:size))", 
    function($offset=0, $size=DEFAULT_PAGE_SIZE) use ($container){
    //get weekly_deal list
    }
);

$container['slim']->get("$prefix/search/:query(/:offset(/:size))", 
    function($cid, $offset=0, $size=DEFAULT_PAGE_SIZE) use ($container){
    //get search list
    }
);

$container['slim']->run();
