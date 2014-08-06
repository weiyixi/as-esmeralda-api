<?php

$container['slim']->get("$prefix/:id/address", function($id) use ($container){
    //get all user addresses
	//case 'get_shipping_address':
		// 是否登录
		if (!($_SESSION['user_id'] > 0)) {
			echo json_encode(array('code' => -1,'url' => "login.php?back=" . urlencode("checkout.php?act=checkout_payment_process")));
			die();
		}
		$address_id = intval($_REQUEST['address_id']);
		if ($address_id)
		{
			$rs = $Address->getAddressById($address_id);
			if($rs)
			{
				echo json_encode(array('code' => 0,'data' => $rs));
			}
			else
			{
				echo json_encode(array('code' => 1));
			}
		}
		else
		{
			echo json_encode(array('code' => 1));
		}
		die();
});

$container['slim']->get("$prefix/:id/address/:aid", function($id) use ($container){
    //get user address
    if($aid == 'default'){
    }
});

$container['slim']->post("$prefix/:id/address", function($id) use ($container){
    //create user address
	//case 'checkout_shipping_address':
		$ajax = isset($_REQUEST['ajax']) ? $_REQUEST['ajax'] : '';

		// 是否登录
		if (!($_SESSION['user_id'] > 0))
		{
			#redirect("login.php?back=" . urlencode("checkout.php?act=checkout_shipping_address"));
			if ($ajax)
			{
				echo json_encode(array('code' => -1,'url' => "login.php?back=" . urlencode("checkout.php?act=checkout_payment_process")));
				die();
			}
			else
			{
				redirect("login.php?back=" . urlencode("cart.php"));
			}
		}

		if (isset($_REQUEST['use']) && intval($_REQUEST['use']) > 0) {
			//$_SESSION['address_id'] = intval($_REQUEST['use']);
			$Address->setDefaultAdressById(intval($_REQUEST['use']));
			redirect("checkout.php?act=checkout_payment_process&shipping_address_id=" . intval($_REQUEST['use']));
		}
		if (isset($_REQUEST['delete']) && intval($_REQUEST['delete']) > 0) {
			$Address->deleteAdressById(intval($_REQUEST['delete']));
			redirect("checkout.php?act=checkout_shipping_address");
		}

		if (isPost()) {
			$r = _save_address();
			$address = $_POST['address'];
			include_once __DIR__ . '/includes/lib_region.php';
			$region = Region::getRegionById($address['country']);
			if (isset($region[0]['region_code'])) {
			    $country_code = $region[0]['region_code'];
			} else {
				$country_code = 'US';
			}

			if (isset($_REQUEST['edit']) && intval($_REQUEST['edit']) > 0)
			{
				if ($r > 0)
				{
					// FIXME 如果是修改了地址，是否使用该地址？
					if ($ajax)
					{
						echo json_encode(array(
						    'code' => 0,
						    'msg' => $r,
							'payment_modules' => get_filter_payment_method($country_code, $currency_code, '', PROJECT_NAME)
						));
						die();
					}
					else
					{
						redirect("checkout.php?act=checkout_payment_process");
					}
				}
			}
			else
			{
				if ($r > 0)
				{
					if ($ajax)
					{
						echo json_encode(array(
							'code' => 0,
							'msg' => $r,
							'payment_modules' => get_filter_payment_method($country_code, $currency_code, '', PROJECT_NAME)
						));
						die();
					}
					else
					{
						redirect("checkout.php?act=checkout_payment_process");
					}
				}
			}
		} elseif (isset($_REQUEST['edit']) && intval($_REQUEST['edit']) > 0) {
			$address_id = intval($_REQUEST['edit']);
			$address = $Address->getAddressById($address_id);
			if (is_array($address) && $address['address_id'] == $address_id) {
			} else {
				//$_SESSION['address_id'] = 0;
				if($ajax)
				{
					echo json_encode(array('code' => 0,'msg' => 0));
					die();
				}
				else
				{
					redirect("checkout.php?act=checkout_shipping_address");
				}
			}
			$address_list = $Address->getAdressByUserId($_SESSION['user_id']);
			$billing_address_list = $Address->getAdressByUserId($_SESSION['user_id'], 'BILLING');
		} else {
			$address_list = $Address->getAdressByUserId($_SESSION['user_id']);
			$billing_address_list = $Address->getAdressByUserId($_SESSION['user_id'], 'BILLING');

			$address = array(
				'address_id' => '0',
				'address_name' => '',
				'user_id' => '0',
				'consignee' => '',
				'first_name' => '',
				'last_name' => '',
				'gender' => '',
				'email' => '',
				'country' => '0',
				'province' => '0',
				'city' => '0',
				'district' => '0',
				'address' => '',
				'zipcode' => '',
				'tel' => '',
				'mobile' => '',
				'sign_building' => '',
				'best_time' => '',
				'province_text' => '',
				'city_text' => '',
				'district_text' => '',
				'country_name' => '',
				'province_name' => '',
				'city_name' => ''
			);
		}

		// {{{ 添加新地址默认国家
		if($lang_code == 'en')
		{
			/*
			 	英语（美元） -》美国
				英语（英镑）-》英国
				英语（澳币） -》 澳大利亚
				英语（新西兰币） -》 新西兰
				英语（加币）-》加拿大
				英语（欧元）-》爱尔兰
				英语其他-》美国
			 */
			switch ($currency_code)
			{
				case 'USD':
					$country_default = 3859;
					break;
				case 'GBP':
					$country_default = 3858;
					break;
				case 'AUD':
					$country_default = 3835;
					break;
				case 'NZD':
					$country_default = 4101;
					break;
				case 'CAD':
					$country_default = 3844;
					break;
				case 'EUR':
					$country_default = 4054;
					break;
				default:
					$country_default = 3859;
					break;
			}

		}
		elseif($lang_code == 'de')
		{
			//德语(欧元） -》德国
			//德语（瑞士法郎） -》 瑞士
			switch ($currency_code)
			{
				case 'EUR':
					$country_default = 4017;
					break;
				case 'CHF':
					$country_default = 4203;
					break;
				default:
					$country_default = 4017;
					break;
			}
		}
		elseif($lang_code == 'fr')
		{
			//法语 -》法国
			$country_default = 4003;
		}
		elseif($lang_code == 'es')
		{
			//西班牙语 -》 西班牙
			$country_default = 4143;
		}
		elseif($lang_code == 'pt')
		{
			//葡萄牙语 -》 葡萄牙
			$country_default = 4120;
		}
		elseif($lang_code == 'it')
		{
			//意大利语 -》 意大利
			$country_default = 4056;
		}
		elseif($lang_code == 'ru')
		{
			//俄语 -》 俄罗斯
			$country_default = 4124;
		}
		elseif($lang_code == 'se')
		{
			//瑞典语 -》 瑞典
			$country_default = 4202;
		}
		elseif($lang_code == 'da')
		{
			//丹麦语 -》 丹麦
			$country_default = 3987;
		}
		elseif($lang_code == 'no')
		{
			//挪威语 -》 挪威
			$country_default = 4108;
		}
		elseif($lang_code == 'fi')
		{
			//芬兰语 -》 芬兰
			$country_default = 4002;
		}
		elseif($lang_code == 'nl')
		{
			//荷兰语 -》 荷兰
			$country_default = 4099;
		}
		else
		{
			$country_default = 3859;
		}
		// }}}

		// {{{
		include_once __DIR__ . '/includes/lib_region.php';
		$allRegion = Region::getAllRegion();
		$tmpx = $allCountry = array();
		foreach ($allRegion as $v) {
			$tmpx[$v['region_id']]['self'] = $v;
			if (0 == $v['parent_id'])
				$allCountry[] = $v;
		}
        /**
         * 【需求】地址下拉框排列顺序控制：在页面读取时，由前端代码控制为指定顺序(按字母排序)
         */
        usort($allCountry, "Region::cmpByName");

		foreach ($tmpx as $k => $v) {
			$tmpx[$v['self']['parent_id']]['children'][$v['self']['region_id']] = & $tmpx[$k];
		}
		// 一般第一级的 parent_id 为 0
		$allRegion = $tmpx[0]['children'];
		unset($tmpx);
		$allRegion_json = json_encode($allRegion);
		// }}}

		// 走到这里说明$_SESSION['address_id'] 没值，需要选择一个地址或者新输入一个地址
		$tplName = 'shipping_address.htm';

		break;

});

$container['slim']->post("$prefix/:id/address/:aid", function($id, $aid) use ($container){
    //update user address
    if($aid == 'default'){
	//case 'ship_to_this_address':
		// 是否登录
		if (!($_SESSION['user_id'] > 0)) {
			//redirect("login.php?back=" . urlencode("checkout.php?act=checkout_shipping_address"));
			redirect("login.php?back=" . urlencode("checkout.php?act=checkout_payment_process"));
		}

		if (isPost()) {
			$address_id = intval($_POST['address_id']);
			if ($address_id > 0) {
				$address = $Address->getAddressById($address_id);
				if (!empty($address)) {
					//$_SESSION['address_id'] = $address_id;
					redirect("checkout.php?act=checkout_payment_process");
				}
				// @FIXME 如果这个地址不是当前登录用户的，则不能使用
				if ($_SESSION['user_id'] != $address['user_id']) {
				    // {{{ log
				    $_log_array = array(
				        "ship_to_this_address: address.user_id not equal session.user_id",
				        json_encode($address),
						'-'
				    );
				    Log::write($_log_array);
				    // }}}
				    logout();
				}
			}

		}
		redirect("checkout.php?act=checkout_shipping_address");
		//break;

        //OR
    //case 'checkout_payment_process':
		if ($ajax)
		{
			if ($address_id)
			{
				$rs = $Address->setDefaultAdressById($address_id);
				if ($rs)
				{
					$default_address_data = $Address->getAddressById($address_id);
				}
				else
				{
					echo json_encode(array(
						'code' => 1,
					));
					die();
				}
			}
			elseif(!$country)
			{
				echo json_encode(array(
					'code' => 1
				));
				die();
			}
		}
        //break;
    }
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
	if (isset($address['province']) && $address['province'] && ($province = intval($address['province'])) > 0)
	{
		$province_text = '';
	}
	else
	{
		$province = 0;
		$province_text = isset($address['province_text']) ? $address['province_text'] : '';
		/*
		 * “省份/州”不一定必填，比如新加坡这些城市国家 if (empty($province_text)) {
		 * alert_back($_LANG['page_checkout_please_select_province'],
		 * $_SERVER['REQUEST_URI']); }
		 */
	}
	if (isset($address['city']) && $address['city'] && ($city = intval($address['city'])) > 0)
	{
		$city_text = '';
	}
	else
	{
		$city = 0;
		$city_text = isset($address['city_text']) ? $address['city_text'] : '';
		if (empty($city_text))
		{
			// {{{ log
			$_log_array = array(
				"checkout_shipping_address: city & city_text are all empty",
				json_encode($address),
				'-'
			);
			Log::write($_log_array);
			// }}}
			if ($ajax)
			{
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_checkout_please_input_city']
				));
				die();
			}
			else
			{
				alert_back($_LANG['page_checkout_please_input_city'], $_SERVER['REQUEST_URI']);
			}
		}
	}
	if (isset($address['district']) && $address['district'] && ($district = intval($address['district'])) > 0)
	{
		$district_text = '';
	}
	else
	{
		$district = 0;
		$district_text = isset($address['district_text']) ? $address['district_text'] : '';
	}

	// {{{ 合并 first_name 和 last_name 为收货人
	$consignee = join(" ", array(
		$address['first_name'],
		$address['last_name']
	));
	$consignee = isset($address['consignee']) && $address['consignee'] ? $address['consignee'] : $consignee;
	$consignee = trim($consignee);
	if (empty($consignee))
	{
		// {{{ log
		$_log_array = array(
			"checkout_shipping_address: consignee is empty",
			json_encode($address),
			'-'
		);
		Log::write($_log_array);
		// }}}
		if ($ajax)
		{
			echo json_encode(array(
				'code' => 1,
				'msg' => $_LANG['page_checkout_please_input_first_name_and_last_name']
			));
			die();
		}
		else
		{
			alert_back($_LANG['page_checkout_please_input_first_name_and_last_name'], $_SERVER['REQUEST_URI']);
		}
	}
	// }}}

	$country = preg_match('/^\d+$/', $address['country']) ? $address['country'] : 0;
	if (empty($country))
	{
		// {{{ log
		$_log_array = array(
			"checkout_shipping_address: country is empty",
			json_encode($address),
			'-'
		);
		Log::write($_log_array);
		// }}}
		if ($ajax)
		{
			echo json_encode(array(
				'code' => 1,
				'msg' => $_LANG['page_checkout_please_select_country']
			));
			die();
		}
		else
		{
			alert_back($_LANG['page_checkout_please_select_country'], $_SERVER['REQUEST_URI']);
		}
	}

	if ($country == 3962/*  && PROJECT_NAME == 'JJsHouse' */)
	{
		if ($tax_code_type == 1 && (strlen($tax_code_value) < 11 || !is_numeric($tax_code_value)))
		{
			if ($ajax)
			{
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_common_cpf_code_error_tip']
				));
				die();
			}
			else
			{
				alert_back($_LANG['page_common_cpf_code_error_tip'], $_SERVER['REQUEST_URI']);
			}
		}
		elseif ($tax_code_type == 2 && (strlen($tax_code_value) < 14 || !is_numeric($tax_code_value)))
		{
			if ($ajax)
			{
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_common_cnpj_code_error_tip']
				));
				die();
			}
			else
			{
				alert_back($_LANG['page_common_cnpj_code_error_tip'], $_SERVER['REQUEST_URI']);
			}
		}
	}
	else
	{
		$tax_code_type = 0;
		$tax_code_value = '';
	}

	if ($country == 3835 || $country == 3859 || $country == 3844)
	{
		if (empty($province))
		{
			// {{{ log
			$_log_array = array(
				"checkout_shipping_address: province is empty",
				json_encode($address),
				'-'
			);
			Log::write($_log_array);
			// }}}
			if ($ajax)
			{
				echo json_encode(array(
					'code' => 1,
					'msg' => $_LANG['page_checkout_please_select_province']
				));
				die();
			}
			else
			{
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
	if (isset($_REQUEST['edit']) && intval($_REQUEST['edit']) > 0)
	{
		$address_id = intval($_REQUEST['edit']);
		$address_info = $Address->getAddressById($address_id);
		if ($address_info['address_type'] == 'SHIPPING')
		{
			$r = $Address->saveShippingAdress($data, $address_id);
		}
		elseif ($address_info['address_type'] == 'BILLING')
		{
			$r = $Address->saveBillingAdress($data, $address_id);
		}
	}
	else
	{
		if ($address['address_type'] != 'BILLING')
		{
			$r = $Address->saveShippingAdress($data);
		}
		else
		{
			$r = true;
		}

		// {{{ 如果没保存billing address 则保存
		if ($r)
		{
			$user_id = (int) $_SESSION['user_id'];
			$is_have_billing_address = $Address->getAdressByUserId($user_id, 'BILLING');
			if ($is_have_billing_address == null && $user_id > 0)
			{
				$r = $Address->saveBillingAdress($data);
			}
		}
		// }}}
	}
	return $r;
}

