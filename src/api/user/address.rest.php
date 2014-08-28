<?php
include_once __DIR__ . '/../../common.php';

$prefix = '/apis/user/:uid/address';
$slim = $container['slim'];

$accept = $slim->request->headers->get('Accept');
$acceptJson = stripos($accept, 'json') ? true : false;

// check login
// if (!($_SESSION['user_id'] > 0)) {
// 	if ($acceptJson) {
// 	    $slim->render('json.tpl', array(
// 	        'code' => -1,
// 	        'url' => "login.php?back=".urlencode("checkout.php?act=checkout_payment_process")
// 	    ));		
// 	} else {
// 		$slim->redirect("/login.php?back=".urlencode("cart.php"));		
// 	}
//     die;
// }
// $userId = (int) $_SESSION['user_id'];

function getUserId($uid){
    global $container;
    if (!function_exists('ssession_status') || session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $userService = $container['user'];
    $userId = 0;
    if($uid == 'self'){
        if(isset($_SESSION['user_id'])){
            $userId = $_SESSION['user_id'];
        }
    }else{
        $user = $userService->getUserByUID($uid);
        $userId = $user->user_id;
    }
    return $userId;
}

$slim->get("$prefix/:aid", function($uid, $aid) use ($container){
	$slim = $container['slim'];
	$addressId = (int) $aid;
	if ($addressId) {
		$userId = getUserId($uid);
		$address = $container['user.address']->getById($userId, $addressId);
		if ($address) {
		    $slim->render('json.tpl',array(
		    	'value' => array(
			        'code' => 0,
			        'data' => $address
			    ),
		        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
		        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
		        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
		    ));
		    die;
		}
	}
    $slim->render('json.tpl', array('code' => 1));
});

$slim->post("$prefix/:aid", function($uid, $aid) use ($container){
	$slim = $container['slim'];
	$addressId = (int) $aid;
	if ($addressId) {
		$userId = getUserId($uid);
		$container['user.address']->setDefault($userId, $addressId, \esmeralda\user\address\AddressService::TYPE_SHIPPING);
	}
	$slim->redirect("/checkout.php?act=checkout_payment_process&shipping_address_id=".$addressId);
});

$slim->delete("$prefix/:aid", function($uid, $aid) use ($container, $acceptJson){
	$slim = $container['slim'];
	$addressId = (int) $aid;
	if ($addressId) {
		$userId = getUserId($uid);
		$rs = $container['user.address']->remove($userId, $addressId);
	}
	if ($acceptJson) {
		$code = 0;
		$msg = 'success';
		if (!$rs) {
			$code = 1;
			$msg = 'error';			
		}
	    $slim->render('json.tpl',array(
	    	'value' => array(
				'code' => $code,
				'msg' => $msg
		    ),
	        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
	        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
	        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
	    ));
	    die;
	} else {
		$slim->redirect("/checkout.php?act=checkout_shipping_address");
	}
});

$slim->post($prefix, function($uid) use ($container, $acceptJson){
	$slim = $container['slim'];
	$userId = getUserId($uid);
    $contentType = $slim->request->headers->get('Content-Type');
    switch($contentType) {
	    case 'application/x-www-form-urlencoded':
	        $address = $slim->request->post('address');
	        break;
	    case 'application/json':
	    default:
	        $body = $slim->request->getBody();
	        $address = json_decode($body, true);
	        break;
    }

	$rs = $container['user.address']->create($userId, $address);
	if ($rs == Validator::VALIDATED) {
		$billingAddress = $container['user.address']->getByType($userId, AddressService::TYPE_BILLING);
		if (empty($billingAddress)) {
			$address['address_type'] = AddressService::TYPE_BILLING;
			$rs = $container['user.address']->create($userId, $address);
		}
	}
	if ($acceptJson) {
	    $slim->render('json.tpl',array(
	    	'value' => array(
				'code' => 0,
				'msg' => $rs
		    ),
	        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
	        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
	        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
	    ));		
	} else {
		$slim->redirect("/checkout.php?act=checkout_payment_process");
	}
});

$slim->post("$prefix/:aid", function($uid, $aid) use ($container, $acceptJson){
	$slim = $container['slim'];
	$addressId = (int) $aid;
	if ($addressId) {
		$userId = getUserId($uid);
	    $contentType = $slim->request->headers->get('Content-Type');
	    switch($contentType) {
		    case 'application/x-www-form-urlencoded':
		        $address = $slim->request->post('address');
		        break;
		    case 'application/json':
		    default:
		        $body = $slim->request->getBody();
		        $address = json_decode($body, true);
		        break;
	    }

		$rs = $container['user.address']->update($userId, $addressId, $address);
		if ($acceptJson) {
		    $slim->render('json.tpl',array(
		    	'value' => array(
					'code' => 0,
					'msg' => $rs
			    ),
		        'json_format' => JSON_FORCE_OBJECT | JSON_PRETTY_PRINT,
		        'APP_WEB_ROOT' => $container['APP_WEB_ROOT'],
		        'PUBLIC_ROOT' => $container['PUBLIC_ROOT'],
		    ));
		    die;
		}
	}

	$slim->redirect("/checkout.php?act=checkout_payment_process");
});

//--old
function _save_address()
{
	global $_LANG, $Address;
	$ajax = $_REQUEST['ajax'];
	$address = $_POST['address'];
	$tax_code_type = (int) $address['tax_code_type'];
	$tax_code_value = $address['tax_code_value'];
	$tax_code_type = $tax_code_type ? $tax_code_type : 0;

	// p($_POST);die();
	if (isset($address['province']) && $address['province'] && ($province = intval($address['province'])) > 0) {
		$province_text = '';
	} else {
		$province = 0;
		$province_text = isset($address['province_text']) ? $address['province_text'] : '';
		/*
		 * “省份/州”不一定必填，比如新加坡这些城市国家 if (empty($province_text)) {
		 * alert_back($_LANG['page_checkout_please_select_province'],
		 * $_SERVER['REQUEST_URI']); }
		 */
	}

	if (isset($address['city']) && $address['city'] && ($city = intval($address['city'])) > 0) {
		$city_text = '';
	} else {
		$city = 0;
		$city_text = isset($address['city_text']) ? $address['city_text'] : '';
		if (empty($city_text)) {
			$_log_array = array(
				"checkout_shipping_address: city & city_text are all empty",
				json_encode($address),
				'-'
			);
			Log::write($_log_array);
			if ($ajax) {
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_checkout_please_input_city']
				));
				die();
			} else {
				alert_back($_LANG['page_checkout_please_input_city'], $_SERVER['REQUEST_URI']);
			}
		}
	}

	if (isset($address['district']) && $address['district'] && ($district = intval($address['district'])) > 0) {
		$district_text = '';
	} else {
		$district = 0;
		$district_text = isset($address['district_text']) ? $address['district_text'] : '';
	}

	// {{{ 合并 first_name 和 last_name 为收货人
	$consignee = join(" ", array($address['first_name'], $address['last_name']));
	$consignee = isset($address['consignee']) && $address['consignee'] ? $address['consignee'] : $consignee;
	$consignee = trim($consignee);
	if (empty($consignee)) {
		$_log_array = array(
			"checkout_shipping_address: consignee is empty",
			json_encode($address),
			'-'
		);
		Log::write($_log_array);
		if ($ajax) {
			echo json_encode(array(
				'code' => 1,
				'msg' => $_LANG['page_checkout_please_input_first_name_and_last_name']
			));
			die();
		} else {
			alert_back($_LANG['page_checkout_please_input_first_name_and_last_name'], $_SERVER['REQUEST_URI']);
		}
	}
	// }}}

	$country = preg_match('/^\d+$/', $address['country']) ? $address['country'] : 0;
	if (empty($country)) {
		$_log_array = array(
			"checkout_shipping_address: country is empty",
			json_encode($address),
			'-'
		);
		Log::write($_log_array);
		if ($ajax) {
			echo json_encode(array(
				'code' => 1,
				'msg' => $_LANG['page_checkout_please_select_country']
			));
			die();
		} else {
			alert_back($_LANG['page_checkout_please_select_country'], $_SERVER['REQUEST_URI']);
		}
	}

	if ($country == 3962/*  && PROJECT_NAME == 'JJsHouse' */) {
		if ($tax_code_type == 1 && (strlen($tax_code_value) < 11 || !is_numeric($tax_code_value))) {
			if ($ajax) {
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_common_cpf_code_error_tip']
				));
				die();
			} else {
				alert_back($_LANG['page_common_cpf_code_error_tip'], $_SERVER['REQUEST_URI']);
			}
		} elseif ($tax_code_type == 2 && (strlen($tax_code_value) < 14 || !is_numeric($tax_code_value))) {
			if ($ajax) {
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_common_cnpj_code_error_tip']
				));
				die();
			} else {
				alert_back($_LANG['page_common_cnpj_code_error_tip'], $_SERVER['REQUEST_URI']);
			}
		}
	} else {
		$tax_code_type = 0;
		$tax_code_value = '';
	}

	if ($country == 3835 || $country == 3859 || $country == 3844) {
		if (empty($province)) {
			$_log_array = array(
				"checkout_shipping_address: province is empty",
				json_encode($address),
				'-'
			);
			Log::write($_log_array);
			if ($ajax) {
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_checkout_please_select_province']
				));
				die();
			} else {
				alert_back($_LANG['page_checkout_please_select_province'], $_SERVER['REQUEST_URI']);
			}
		}
	}

	$data = array(
		'user_id' => $_SESSION['user_id'],
		'consignee' => $consignee,
		'first_name' => $address['first_name'],
		'last_name' => $address['last_name'],
		'gender' => $address['gender'],
		'country' => $country,
		'tax_code_type' => $tax_code_type,
		'tax_code_value' => $tax_code_value,
		'province' => $province,
		'province_text' => $province_text,
		'city' => $city,
		'city_text' => $city_text,
		'district' => $district,
		'district_text' => $district_text,
		'address' => $address['address_1'],
		'sign_building' => $address['address_2'],
		'zipcode' => $address['zip'],
		'tel' => $address['phone'],
		'mobile' => isset($address['mobile']) ? $address['mobile'] : '',
		'email' => isset($address['email']) ? $address['email'] : '',
		'best_time' => isset($address['best_time']) ? $address['best_time'] : ''
	);

	if (isset($_REQUEST['edit']) && intval($_REQUEST['edit']) > 0) {
		$address_id = intval($_REQUEST['edit']);
		$address_info = $Address->getAddressById($address_id);
		if ($address_info['address_type'] == 'SHIPPING') {
			$r = $Address->saveShippingAdress($data, $address_id);
		} elseif ($address_info['address_type'] == 'BILLING') {
			$r = $Address->saveBillingAdress($data, $address_id);
		}
	} else {
		if ($address['address_type'] != 'BILLING') {
			$r = $Address->saveShippingAdress($data);
		} else {
			$r = true;
		}

		// {{{ 如果没保存billing address 则保存
		if ($r) {
			$user_id = (int) $_SESSION['user_id'];
			$is_have_billing_address = $Address->getAdressByUserId($user_id, 'BILLING');
			if ($is_have_billing_address == null && $user_id > 0) {
				$r = $Address->saveBillingAdress($data);
			}
		}
		// }}}
	}
	return $r;
}

$slim->run();