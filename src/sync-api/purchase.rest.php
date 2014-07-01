<?php
include_once dirname(__DIR__) . '/common.php';

use esmeralda_service\base\ApiLogFactory;

$logger = ApiLogFactory::get('purchase.rest');
$logger->error('hello world.');

$prefix = '/sync-apis/purchase';

$container['slim']->post($prefix, function () {

	if (isset($_POST['orderSn'])) {
		
	}

	try {
		// $result = $mailer->send($message);
	} catch(\AWSConnectionError $e) {
		echo "AWSConnectionError";
	} catch(\Exception $e){
		print_r($e);
	}
});

$container['slim']->run();
