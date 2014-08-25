<?php
$uri = $_SERVER['REQUEST_URI'];
$subapi = preg_replace('/\/apis\/user\/([\d\w]+)\/(\w+).*/', '/user/$2.rest.php', $uri);
if($subapi != $uri){
    include __DIR__ . $subapi;
    die;
}

include_once __DIR__ . '/../common.php';

$prefix = '/apis/user';

function isGotoCheckoutPage() {
	$pre_login_cart_total = 0;
	if (isset($_SESSION['preLoginShoppingCartGoodsTotal'])) {
		$pre_login_cart_total = (int) $_SESSION['preLoginShoppingCartGoodsTotal'];
		unset($_SESSION['preLoginShoppingCartGoodsTotal']);
	}

	$login_cart_total = 0;
	if (isset($_SESSION['shoppingCartGoodsTotal'])) {
		$login_cart_total = (int) $_SESSION['shoppingCartGoodsTotal'];
	}

	if ($pre_login_cart_total > 0 && $login_cart_total > 0 && $pre_login_cart_total == $login_cart_total) {
		return true;
	}
	return false;
}

$container['slim']->post("$prefix/logout", function() use ($container){
    $app = $container['slim'];
    $sessionId = $app->request->post('sessionId');
    $rs = $userService->logout($sessionId);
    $container['slim']->render('json.tpl', array(
        'value' => $rs,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});

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

$container['slim']->post("$prefix/login", function() use ($container){
    //update user
    $app = $container['slim'];
    $userService = $container['user'];
    $back = $app->request->get('back');
    $sessionId = $app->request->post('sessionId');
    $loginInfo = $app->request->post('login');
    $r = array(
        'error' => 1,
        'back' => $back,
		'msg' => 'page_login_miss_email',
    );
    if (!empty($loginInfo['email']) && !empty($loginInfo['password'])) {
        session_id($sessionId);
        session_start();
        $email = $loginInfo['email'];
        $pswd = md5($loginInfo['password']);
        if (!preg_match("/^[\w\.\-]+@[\w\-]+(\.[\w\-]+)+$/i", trim($email))) {
            $r['msg'] = 'page_login_invalid_email';
        } else {
            $rs = $userService->login($email, $pswd);
            if ($rs === true) {
                $r['error'] = 0;
                $r['msg'] = 'page_login_success';
            } else {
                $r['msg'] = 'page_login_failed';
            }
        }
    }
    $container['slim']->render('json.tpl', array(
        'value' => $r,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});

$container['slim']->post("$prefix/register", function() use ($container){
    $app = $container['slim'];
    $userService = $container['user'];
    $back = $app->request->get('back');
    $email = $app->request->post('email');
    $from = $app->request->post('from');
    $sessionId = $app->request->post('sessionId');
    if ( ! empty($sessionId)) {
        session_id($sessionId);
        session_start();
    }
    $r = array(
        'error' => 1,
        'back' => $back,
		'msg' => 'register_failed',
    );
    if ($from == 'facebook') {
        // @TODO register from facebook
    } else {
        $reg = $app->request->post('reg');
        if (empty($reg['user_name'])) {
            $email = $reg['email'];
            $emails = explode('@', $email);
            $reg['user_name'] = $emails[0];
        }
        $reg['password'] = md5($reg['password']);
        $reg['password_again'] = md5($reg['password_again']);
        $rs = $userService->register($reg);
        if ($rs) {
            $r['error'] = 0;
            $r['msg'] = 'register_success';
        }
    }

    $container['slim']->render('json.tpl', array(
        'value' => $r,
        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
    ));
});


$container['slim']->post("$prefix", function() use ($container){
    //create user
});

$container['slim']->post("$prefix/:id", function($id) use ($container){
    //update user
});


$container['slim']->run();
