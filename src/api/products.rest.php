<?php
include_once __DIR__ . '/../common.php';

use esmeralda\product\ProductServiceWrapper;
use esmeralda\base\Util;

$prefix = '/apis/products';

function parseIds($param_ids){
    $ids = array();
    if (strstr($param_ids, ':')){
		$ids = explode(":", $param_ids);
		$start = $ids[0];
		$len = $ids[1];
        $ids = range($start, $start + $len);
	} else if (strstr($param_ids, '-')) {
		$ids = explode("-", $param_ids);
		$start = $ids[0];
		$end = $ids[1];
        $ids = range($start, $end);
	} else {
		$ids = explode(",", $param_ids);
	}
    return $ids;
}

function getProducts($ids, $status, $lang = null) {
    global $container;

    switch($status) {
        case 'any':
            $products = $container['product']->getProducts($ids, $lang, -1, -1, -1);
            break;
        case 'active':
        default:
            $products = $container['product']->getProducts($ids, $lang);
            break;
    }
    return $products;
}

$container['slim']->get("$prefix/base/:ids/:domain", function($ids, $domain) use ($container){
    $ids = parseIds($ids);
    // limit 200
    if (count($ids) > 200) {
        $ids = array_slice($ids, 0, 200);        
    }

    $slim = $container['slim'];
    $status = $slim->request->params('status');
    if(null == $status){
        $status = 'active';
    }
    $lang = $slim->request->params('lang');
    $products = getProducts($ids, $status, $lang);

    $slim->render('json.tpl', array(
        'value' => $products,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});

$container['slim']->get("$prefix/styles/:ids", function($ids) use ($container){
    $ids = parseIds($ids);
    // limit 200
    if (count($ids) > 200) {
        $ids = array_slice($ids, 0, 200);        
    }

    $styleFeature = $container['feature']->getFeature('product-style', '1.0');
    $styleInst = $container['feature']->getInstance($styleFeature->id, Util::conf('domain'), "style_common");
    $config = json_decode($styleInst->config);
    $noStyleGoods = isset($config->noStyleGoods) ? $config->noStyleGoods : array();
    $ids = array_diff($ids, $noStyleGoods);

    $slim = $container['slim'];
    $status = $slim->request->params('status');
    if(null == $status){
        $status = 'active';
    }
    $products = getProducts($ids, $status);

    $productsStyle = array();
    $productServiceWrapper = ProductServiceWrapper::getInstance($container['product']);
    $shopConfigCodeMap = $container['config']->getConfigCodeMap();
    $catTree = $container['category']->getTree(null, -1);
    if ($productServiceWrapper instanceof ProductServiceWrapper) {
        foreach ($products as $goodsId => $goods) {
            $parentCategory = $catTree->getMainParent($goods->cat_id);
            $parentCategoryId = $parentCategory->id;
            $goodsAllAttrTree = $productServiceWrapper->getProductAttrTree($goodsId, null, -1, 0, 1);
            $styleTreeData = $productServiceWrapper->getProductStyleTree($goods, null, array(
                'shopConfigCodeMap' => $shopConfigCodeMap,
                'catParentId' => $parentCategoryId,
                'goodsId' => $goodsId,
                'goodsAllAttrTree' => $goodsAllAttrTree,
            ));
            $goodsStyle = array();
            $styleTree = $styleTreeData['tree'];
            $parentStyles = $styleTree->getChildren($styleTree->getRootNodeId());
            foreach ($parentStyles as $parentStyle) {
                $parentNodeId = isset($parentStyle->manualId) ? $parentStyle->manualId : $parentStyle->id;
                $parentStyle->children = $styleTree->getChildren($parentNodeId);
                $goodsStyle[$parentNodeId] = $parentStyle;
            }
            $productsStyle[$goodsId] = $goodsStyle;
        }
    }

    $slim->render('json.tpl', array(
        'value' => $productsStyle,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
    ));
});

$container['slim']->get("$prefix/ids/:range", function($range) use ($container){
    list($min, $limit) = explode(':', $range);
    $slim = $container['slim'];
    $status = $slim->request->params('status');
    if(null == $status){
        $status = 'active';
    }

    $query = array('min_id'=>$min, 'limit'=>$limit);
    switch($status) {
        case 'any':
            $productIds = $container['product']->_getProductIds($query, -1, -1, -1);
            break;
        case 'active':
        default:
            $productIds = $container['product']->_getProductIds($query, 1, 0, 1);
            break;
    }

    $slim->render('json.tpl', array(
        'value' => $productIds,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
    ));
});

$container['slim']->run();