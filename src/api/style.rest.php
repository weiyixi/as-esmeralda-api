<?php

include_once __DIR__ . '/../common.php';

use esmeralda\style\StyleDao;
use esmeralda\style\DbStyleService;

$prefix = '/apis/style';
const DEFAULT_PAGE_SIZE = 24;

function verifyToken($token) {
    return $token == 'd41d8cd98f00b204e9800998ecf8427e';
}

/*
 Get all raw styles updated between specified times
 Hour and Minutes is optional
 Use '_' instance of ' ' is recommended
 Eg. /apis/style/2015-01-01_00:00:00/0/100
 */
$container['slim']->get("$prefix/:beginTime/:minStyleId(/:limit)",
    function($beginTime, $minStyleId, $limit=DEFAULT_PAGE_SIZE) use ($container){

    // verify token
    $token = $container['slim']->request->get('token');
    if (!verifyToken($token)) {
        echo 'Not Authorized.'; die;
    }

    // use db style service, without cache
    $styleDao = new StyleDao($container);
    $styleService = new DbStyleService($styleDao);

    // get all styles
    $beginTime = str_replace('_', ' ', $beginTime);
    $styleIds = $styleService->_getStyleIds(array(
        'begin' => $beginTime,
        'min_id' => $minStyleId,
        'limit' => $limit,
    ));
    $styleTree = $styleService->getByIds($styleIds);
    $styles = $styleTree->getAllNodes();

    // append style name
    foreach ($styles as $sId => $style) {
        if (!empty($styles[$style->parent_id]->oname)) {
            $style->oname = $styles[$style->parent_id]->oname;
        }
    }
    // remove invalid node
    foreach ($styles as $sId => $style) {
        if (!in_array($sId, $styleIds)) {
            unset($styles[$sId]);
        }
    }

    $container['slim']->render('json.tpl', array(
        'value' => $styles,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
    ));

});

$container['slim']->run();
