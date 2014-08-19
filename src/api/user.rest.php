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

//@TODO get?
//$container['slim']->post("$prefix/logout", function() use ($container){
$container['slim']->get("$prefix/logout", function() use ($container){
    $app = $container['slim'];
    //$sessionId = $app->request->post('sessionId');
    $sessionId = $app->request->get('sessionId');
    session_id($sessionId);
    session_start();

    $reftag = isset($_SESSION['JJSREF']) ? $_SESSION['JJSREF'] : '';
    $userService = $container['user'];
    /* @TODO facebook logout
	if ($_SESSION['reg_recommender'] == 'facebookLogin') {
		include_once (ROOT_PATH . 'includes/facebook/facebook.php');
		$facebook = new Facebook(array(
			'appId' => FB_APPID,
			'secret' => FB_SECRET,
			'cookie' => true
		));
		$logout_url = $facebook->getLogoutUrl();
		$session = $facebook->getUser();
		if (!empty($session)) {
			$facebook->destroySession();
			setCookie('fbs_' . FB_APPID, null, time() - 3600, '/', COOKIE_DOMAIN);
			header("Location: $logout_url");
			die();
		}
	}
     */
    $rs = $userService->logout();
    $_SESSION['JJSREF'] = $reftag;
    //echo json_encode($rs);die;

    $back = $app->request->get('back');
	$back = preg_replace("/isLoginBack=(\d+)/i", "", $back);
    if (empty($back)) {
        $back = $app->request->getReferrer();
    }
    header("Location: $back");
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
	//$is_success = isset($r['error']) && $r['error'] == 0 ? 'SUCCESS' : 'FAILED';
    //Record::insert_login_record($login_info['email'] , '0' , $is_success);
	// 只针对购物车页面点击continue checkout按钮后操作处理，$back == 'cart.php' 是根据checkout页面checkout_login后台back返回的参数判断的
    $r['back'] = isGotoCheckoutPage() && $back == 'cart.php' ? "checkout.php?act=checkout_payment_process" : $back;
    if ($back == 'cart.php') {
        $r['back'] .= '?isLoginBack=1';
    }
    echo json_encode($r);die;
});

$container['slim']->post("$prefix/register", function() use ($container){
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

    $back = isset($_REQUEST['back']) ? $_REQUEST['back'] : $WEB_ROOT;
    $is_ajax = isset($_REQUEST['is_ajax']) ? $_REQUEST['is_ajax'] : '';
    $checkEmail = isset($_REQUEST['checkEmail']) ? $_REQUEST['checkEmail'] : '';
    $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
    $from = isset($_REQUEST['from']) ? $_REQUEST['from'] : PROJECT_NAME_LOWER;
    $userInfo = $_SESSION;
});


$container['slim']->post("$prefix", function() use ($container){
    //create user
});

$container['slim']->post("$prefix/:id", function($id) use ($container){
    //update user
});


$container['slim']->run();
