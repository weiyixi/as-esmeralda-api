<?php

use Monolog\Formatter\JsonFormatter;

include_once __DIR__ . '/../modules/lestore_common.php';

use lestore_base\app\model\ResponseCache;
use lestore_base\app\service\G11N;
use lestore_product\app\model\ProductDetail;
use lestore_product\app\model\Product;
use lestore_product\app\service\DbProductService;
use lestore_product\app\dao\ProductDao;
use lestore_product\app\service\DbProductServiceWrapper;
use lestore_product_list\app\dao\ProductListDao;
use lestore_product_list\app\service\DBDomainProductListService;


$prefix = '/apis/product/:domain';

$container['product'] = $container->share(function($c){
	$dao = new ProductDao($c['db']);
    return new DbProductService($dao); // without cache
// 	return new DbProductServiceWrapper($dao); // with cache (service cache)
});

$container['product_list'] = $container->share(function($c){
	$plDao = new ProductListDao($c['db']);
	return new DBDomainProductListService($plDao);
});


function encode(&$item, $key)
{
	$item = utf8_encode($item);
}

function encodeArray(&$array){
	array_walk_recursive($array, 'encode');
}
	
// $container['slim']->add(new ResponseCache()); // cache response (api cache)

// get product base info
$container['slim']->get("$prefix/:id(/:lang)", function($domain, $id, $lang = 'en') use ($container){
	// get product ids list, used for OFFSET&SIZE query
	$productList = $container['product_list']->getProductIds($domain);
	$container['product']->setDomainInfo($domain, $productList);
	
	if (strstr($id, ":")){
		$ids = explode(":", $id);
		$offset = $ids[0];
		$size = $ids[1];
		$products = $container['product']->getProductsByOffset($offset, $size);
		$nls = $container['product']->getProductsNlsByOffset($offset, $size, $lang);
	} else {
		$ids = explode(",", $id);
		$products = $container['product']->getProductsByIds($ids);
		$nls = $container['product']->getProductsNlsByIds($ids, $lang);
	}
	
	$langId = G11N::langId($lang);
	$nl = $nls[$langId];
	
	encodeArray($nl);
	
	// nlize
// 	foreach ($products as $product){
// 		//nlize product base info
// 		$product = $container['product']->nlize($product, $nl);
// 	}
// 	echo json_encode($products, JSON_FORCE_OBJECT);
	
	$container['slim']->render('products.tpl', array(
			'service' => $container['product'],
			'products' => $products,
			'nls' => $nl,
			'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
			'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
	));

});

// get product details info
$container['slim']->get("$prefix/:id/details(/:lang)", function($domain, $id, $lang = 'en') use ($container){
	// get product ids list, used for OFFSET&SIZE query
	$productList = $container['product_list']->getProductIds($domain);
	$container['product']->setDomainInfo($domain, $productList);
	
	$cache = ResponseCache::getCache();
	$cacheKey = $container['slim']->request()->getPath();
	
	if($cache->contains($cacheKey)){
		$json = $cache->fetch($cacheKey);
		$container['slim']->render('json.tpl', array(
			'json' => $json
		));
	} else {
		if (strstr($id, ":")){
			$ids = explode(":", $id);
			$offset = $ids[0];
			$size = $ids[1];
			$products = $container['product']->getProductDetailsByOffset($offset, $size);
			$nls = $container['product']->getProductsNlsByOffset($offset, $size, $lang);
			$tagNls = $container['product']->getTagsNlsByOffset($offset, $size, $lang);
		} else {
			$ids = explode(",", $id);
			$products = $container['product']->getProductDetailsByIds($ids);
			$nls = $container['product']->getProductsNlsByIds($ids, $lang);
			$tagNls = $container['product']->getTagsNlsByIds($ids, $lang);
		}
		
		// TODO move to service
		// get attribute
		$aids = array();
		foreach ($products as $product){
			$kids = array_keys($product->attributes);
			$aids = array_merge($aids, $kids);
			foreach ($kids as $kid){
				$vids = array_values($product->attributes[$kid]);
				$aids = array_merge($aids, $vids);
			}
			$aids = array_values(array_unique($aids, SORT_STRING));
		}
		$attrNls = $container['product']->getAttributesNls($aids, $lang);
		
		// TODO move to service
		// get styles
		$sids = array();
		foreach ($products as $product){
			$kids = array_keys($product->styles);
			$sids = array_merge($sids, $kids);
			foreach ($kids as $kid){
				$vids = array_values($product->styles[$kid]);
				$sids = array_merge($sids, $vids);
			}
			$sids = array_values(array_unique($sids, SORT_STRING));
		}
		$styleNls = $container['product']->getStylesNls($sids, $lang);

		// get nl
		$langId = G11N::langId($lang);
		$nl = $nls[$langId];
		$attrNl = $attrNls[$langId];
		$styleNl = $styleNls[$langId];
		
		// encoding special charsets
		encodeArray($nl);
		encodeArray($attrNl);
		encodeArray($styleNl);
		
		// nlize products
		foreach ($products as $product){
			//nlize product base info
			$product = $container['product']->nlize($product, $nl);

			// nlize product attributes
			$attributes = array();
			foreach ($product->attributes as $key => $values){
				if(isset($attrNl[$key])){
					$attributes[$key]['name'] = $attrNl[$key]['name'];
					$attrValues = array();
					foreach ($values as $value){
						if(isset($attrNl[$value])){
							$attrValues[$value]['value'] = $attrNl[$value]['value'];
						}
					}
					$attributes[$key]['values'] = $attrValues;
				}
			}
			$product->attributes = $attributes;
			
			// nlize product styles
			$styles = array();
			foreach ($product->styles as $key => $values){
				if(isset($styleNl[$key])){
					$styles[$key]['name'] = $styleNl[$key]['value'];
					$styleValues = array();
					foreach ($values as $value){
						if(isset($styleNl[$value])){
							$styleValues[$value]['value'] = $styleNl[$value]['value'];
						}
					}
					$styles[$key]['values'] = $styleValues;
				}
			}
			$product->styles = $styles;
		}
		
		echo json_encode($products, JSON_FORCE_OBJECT);
	}
});

// $container['slim']->get("$prefix/:id/nls(/:lang)", function($domain, $id, $lang = 'en') use ($container){
// 	$timeStart = microtime(true);
// 	$container['product']->setDomain($domain);
	
// 	$cache = ResponseCache::getCache();
// 	$cacheKey = $container['slim']->request()->getPath();
	
// 	if($cache->contains($cacheKey)){
// 		$json = $cache->fetch($cacheKey);
// 		$container['slim']->render('json.tpl', array(
// 			'json' => $json
// 		));
// 	} else {
// 		if(strstr($id, "-")){
// 			$ids = explode("-", $id);
// 	// 		$products = $container['product']->getProductDetailsByRange($ids);
// 	// 		$nls = $container['product']->getProductsNls($ids);
// 	// 		$tagNls = $container['product']->getTagsNls($ids);
// 		} elseif (strstr($id, ":")){
// 			$ids = explode(":", $id);
// 			$offset = $ids[0];
// 			$size = $ids[1];
// 			$products = $container['product']->getProductDetailsByOffset($offset, $size);
// 			$nls = $container['product']->getProductsNlsByOffset($offset, $size, $lang);
// 			$tagNls = $container['product']->getTagsNlsByOffset($offset, $size, $lang);
// 		} else {
// 			$ids = explode(",", $id);
// 			$products = $container['product']->getProductDetailsByIds($ids);
// 			$nls = $container['product']->getProductsNlsByIds($ids, $lang);
// 			$tagNls = $container['product']->getTagsNlsByIds($ids, $lang);
// 		}
		
// 		// get attribute
// 		$aids = array();
// 		foreach ($products as $product){
// 			$kids = array_keys($product->attributes);
// 			$aids = array_merge($aids, $kids);
// 			foreach ($kids as $kid){
// 				$vids = array_values($product->attributes[$kid]);
// 				$aids = array_merge($aids, $vids);
// 			}
// 			$aids = array_unique($aids);
// 		}
// 		$attrNls = $container['product']->getAttributesNls($aids);
		
// 		// get styles
// 		$sids = array();
// 		foreach ($products as $product){
// 			$kids = array_keys($product->styles);
// 			$sids = array_merge($sids, $kids);
// 			foreach ($kids as $kid){
// 				$vids = array_values($product->styles[$kid]);
// 				$sids = array_merge($sids, $vids);
// 			}
// 			$sids = array_unique($sids);
// 		}
// 		$sids = array_values(array_unique($sids));
// 		$styleNls = $container['product']->getStylesNls($sids);
		
// 		$langId = 1;
// 		$attrNl = $attrNls[$langId];
// 		$styleNl = $styleNls[$langId];
		
// 		// nlize
// 		foreach ($products as $product){
// 			//nlize product base info
// 			$product = $container['product']->nlize($product, $nls[$langId]);
				
// 			// nlize product attributes
// 			$attributes = array();
// 			foreach ($product->attributes as $key => $values){
// 				$attributes[$key]['name'] = $attrNl[$key]['name'];
// 				$attrValues = array();
// 				foreach ($values as $value){
// 					$attrValues[$value]['value'] = $attrNl[$value]['value'];
// 				}
// 				$attributes[$key]['values'] = $attrValues;
// 			}
// 			$product->attributes = $attributes;
				
// 			// nlize product styles
// 			$styles = array();
// 			foreach ($product->styles as $key => $values){
// 				$styles[$key]['name'] = $styleNl[$key]['value'];
// 				$styleValues = array();
// 				foreach ($values as $value){
// 					$styleValues[$value]['value'] = $styleNl[$value]['value'];
// 				}
// 				$styles[$key]['values'] = $styleValues;
// 			}
// 			$product->styles = $styles;
// 		}
		
// 		echo json_encode($products);
		
// 		$container['slim']->render('product_nls.tpl', array(
// 				'service' => $container['product'],
// 				'nls' => $nls,
// 				'attrNls' => $attrNls,
// 				'styleNls' => $styleNls,
// 				'tagNls' => $tagNls,
// 				'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
// 				'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
// 		));
// 	}
// });



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
