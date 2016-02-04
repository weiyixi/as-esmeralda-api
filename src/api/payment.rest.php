<?php

include_once __DIR__ . '/../modules/lestore_common.php';

$prefix = '/apis/payment';
$container['payment'] = function($c){
    //return new PaymentService();
};

$container['slim']->get("$prefix/:id", function($id) use ($container){
    //get payment method
});

$container['slim']->run();

/*

	case 'get_payment_modules':
		$country_id = (int) $_REQUEST['country'];
		include_once __DIR__ . '/includes/lib_region.php';
		$country_code = Region::getCodeByCountryId($country_id);
		$payment_methods = get_filter_payment_method($country_code, $currency_code, '', PROJECT_NAME);
		if ($payment_methods) {
			$r = array(
				'code' => 0,
				'payment_modules' => $payment_methods
			);
		} else {
			$r = array(
					'code' => 1,
			);
		}
		echo json_encode($r);
		die();
		break;
 */
