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


//{{{ GET: $prefix/base/:ids/:domain
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
//}}}
//{{{ GET: $prefix/styles/:ids
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
//}}}
//{{{ GET: $prefix/ids/:range
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
//}}}
//{{{ GET: $prefix/recent/:domain
$container['slim']->get("$prefix/recent/:domain", function($domain) use ($container){
    $existHistory = array();
    if(isset($_COOKIE['goods_view_history'])){
        $existHistory = explode(',', $_COOKIE['goods_view_history']);
    }
    // limit 200
    $limit = 12;
    for ($i = 0; $i < min($limit, count($existHistory)); $i++ ) {
        if ($existHistory[$i] > 0) {
            $newHistory[] = $existHistory[$i];
        }
    }

    $slim = $container['slim'];
    $status = $slim->request->params('status');
    if(null == $status){
        $status = 'active';
    }
    $lang = $slim->request->params('lang');
    $products = getProducts($newHistory, $status, $lang);
    // @TODO
    //$favorites = $Shopping->getGoodsFavoritesCount($goods_id);

    $goods_name_show_len = 37;
    foreach ($products as &$product) {
		if (strlen($product->name) > $goods_name_show_len + 3) {
			$goods_name_arr = explode(' ', $product->name);
			$goods_name_new = '';
			foreach ($goods_name_arr as $_k => $_v) {
				if (strlen($goods_name_new) + strlen($_v) <= $goods_name_show_len) {
					$goods_name_new .= ' ' . $_v;
				} else {
					break;
				}
			}
			$product->name = $goods_name_new . '...';
		}
		$product->goods_thumb = 's128/' . $product->goods_thumb;
    }
    unset($product);

	//setcookie('goods_view_history', $goods_view_history, time() + 365 * 24 * 3600, '/');
    //$memcache->set('goods_view_history_' . md5($goods_view_history), $r, 0, 3600 * 3);
    $data = array(
        'favorites' => 0,
        'view_history' => $products,
    );

    $slim->render('json.tpl', array(
        'value' => $data,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}

$container['slim']->run();
