<?php
include_once __DIR__ . '/../common.php';

$prefix = '/apis/product';

//{{{ GET: $prefix/:id
$container['slim']->get("$prefix/:id", function($id) use ($container){
    $slim = $container['slim'];
    $status = $slim->request->params('status');
    if(null == $status){
        $status = 'active';
    }
    switch($status){
    case 'any':
        $product = $container['product']->getProduct($id, null, -1, -1, -1);
        break;
    case 'active':
    default:
        $product = $container['product']->getProduct($id, null);
        break;
    }
    $slim->render('json.tpl', array(
        'value' => $product,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}
//{{{ GET: $prefix/:id/styles
$container['slim']->get("$prefix/:id/styles", function($id) use ($container){
    $slim = $container['slim'];
    $styles = $container['product']->getStyles($id);
    if(empty($styles)){
        $slim->render('json.tpl', array(
            'value' => array('code' => 1, 'msg' => "style for product $id not found"),
            'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        ),
        404);
    }else{
        if(!empty($styles['tree'])){
            $styles['tree'] = $styles['tree']->raw();
        }
        $slim->render('json.tpl', array(
            'value' => $styles,
            'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        ));
    }
});
//}}}
//{{{ GET: $prefix/:id/detail
$container['slim']->get("$prefix/:id/detail", function($id) use ($container){
    $detail = $container['product']->getProductDetail($id);
    $container['slim']->render('json.tpl', array(
        'value' => $detail,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}

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
