<?php
include_once dirname(__DIR__) . '/common.php';

use esmeralda\category\attribute\RawCacheAttributeFeeder;
use esmeralda\category\attribute\RawAttributeService;
use esmeralda\category\CategoryResource;

$prefix = '/apis/category/:lang';

$container['slim']->get("$prefix/:id", function($lang, $id) use ($container){
    $slim = $container['slim'];
    function recurse(&$category, &$categoryS, &$cnl, &$slim, $lang){
        $category = $categoryS->nlize($category, $cnl);
        $category->apiurl = $slim->urlFor('category', array('lang'=>$lang, 'id'=>$category->id));
        $category->children = $categoryS->getChildren($category->id());
        foreach($category->children AS $child){
            recurse($child, $categoryS, $cnl, $slim, $lang);
        }
    };
    $category = $container['category']->getCategory($id);
    $categoryS = $container['category'];
    $cnl = $container['category']->getNl($lang);
    recurse($category, $categoryS, $cnl, $slim, $lang);

    $slim->render("category.tpl", array(
        'category' => $category,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));

})->name('category');

$container['slim']->get("$prefix/:id/topsales", function($lang, $id) use ($container){
    //get top sales
});

$container['slim']->get("$prefix/:id/filter(/:params+)", function($lang, $id, $params = array()) use ($container){
    $category = $container['category']->getCategory($id);
    $feeder = new RawCacheAttributeFeeder($container);
    $attributeS = new RawAttributeService($feeder, $category->id());
    $anl = $attributeS->getNl('en');
    $baseurl = $container['slim']->urlFor('category', array('lang' => $lang, 'id' => $id));
    $attributeSel = new CategoryResource($attributeS, $anl, 
        $params, $category, "$baseurl/filter"); 
    //$langId = G11N::langId($lang);
    //$goods = $listS->getProducts($attributeSel->buildQuery($langId));

    $container['slim']->render('filter.tpl', array(
        'attributeS' => $attributeS,
        'attrs' => $attributeSel->getEnhancedAttrs(),
        'anl' => $anl,
        'sel' => $attributeSel,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});

$container['slim']->run();
