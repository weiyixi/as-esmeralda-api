<?php
$uri = $_SERVER['REQUEST_URI'];
$subapi = preg_replace('/\/apis\/user\/([\d\w]+)\/(\w+).*/', '/user/$2.rest.php', $uri);
if($subapi != $uri){
    include __DIR__ . $subapi;
    die;
}

include_once __DIR__ . '/../common.php';

$prefix = '/apis/user';

//{{{ GET: $prefix/:uid
$container['slim']->get("$prefix/:uid", function($uid) use ($container){
    $userService = $container['user'];
    $user = null;
    if($uid == 'self'){
        if(isset($_SESSION['user_id'])){
            $userId = $_SESSION['user_id'];
            $user = $userService->getUser($id);
        }
    }else{
        $user = $userService->getUserByUID($uid);
    }
    $container['slim']->render('json.tpl', array(
        'value' => $user,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});
//}}}

$container['slim']->post("$prefix", function() use ($container){
    //create user
});

$container['slim']->post("$prefix/:id", function($id) use ($container){
    //update user
});

$container['slim']->run();
