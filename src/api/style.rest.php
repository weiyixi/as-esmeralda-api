<?php

include_once __DIR__ . '/../common.php';

use esmeralda\style\StyleDao;
use esmeralda\style\DbStyleService;

$prefix = '/apis/style';
const DEFAULT_PAGE_SIZE = 24;

/*
 Get all raw styles updated between specified times
 Hour and Minutes is optional
 Use '_' instance of ' ' is recommended
 Eg. /apis/style/2015-01-01_00:00:00/0/100
 */
$container['slim']->get("$prefix/:beginTime/:minStyleId(/:limit)",
    function($beginTime, $minStyleId, $limit=DEFAULT_PAGE_SIZE) use ($container){

    // use db style service, without cache
    $styleDao = new StyleDao($container);
    $styleService = new DbStyleService($styleDao);

    $beginTime = str_replace('_', ' ', $beginTime);
    $styleIds = $styleService->_getStyleIds(array(
        'begin' => $beginTime,
        'min_id' => $minStyleId,
        'limit' => $limit,
    ));
    $styleTree = $styleService->getByIds($styleIds);
    $styles = $styleTree->getAllNodes();

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
