<?php
/**
 * run in cli: php AutoPin.php projectName[JS(default)] minProductId[0(default)] 
 */

if (isset($_SERVER['HTTP_HOST'])) {
    die('Only runs in CLI mode.');
}

$projectCode = isset($argv[1]) ? $argv[1] : 'JS';
$projectCodeMap = include_once('./etc/projects.config.php');
if (!isset($projectCodeMap[$projectCode]) || empty($projectCodeMap[$projectCode])) {
	echo "Code: ".$projectCode.". No matched project.\n";
	die;
}
$projectName = $projectCodeMap[$projectCode];
$specifiedMinPId = isset($argv[2]) ? $argv[2] : 0;

require_once './etc/auth.config.php';
// Convert Plural to Singular or Vice Versa in English
require_once './lib/Inflector.php';
$inflector = new Inflector();

$categoryApi = 'https://api.opvalue.com/apis/category/en/all';
$productIdsApiRaw = 'https://api.opvalue.com/apis/products/ids/#MIN#:#LIMIT#';
$productsApiRaw = 'https://api.opvalue.com/apis/products/base/#IDS#/jjshouse?lang=en';
$apiFetchInterval = 1; // sec
$pinInterval = mt_rand(20, 40); // sec

$projectNameLower = strtolower($projectName);
$domainWhole = "http://www.{$projectNameLower}.com/";
$defaultCdn = "http://d3bvl598xc7qos.cloudfront.net/upimg/{$projectNameLower}/o400/";

$autoPinConf = include_once('etc/autopin.config.php');

function curlFetch($url) {
	global $authName, $authPwd, $http_proxy_host, $http_proxy_port;

	$ch = curl_init();
    if(!empty($http_proxy_host)){
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_PROXY, "$http_proxy_host:$http_proxy_port");
    }
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, "{$authName}:{$authPwd}");
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 600);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate,sdch');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
	$retry = 0;
	do {
		$data = curl_exec($ch);
		$retry++;
	} while (curl_errno($ch) > 0 && $retry < 3);

	if (empty($data)) {
		$dataDump = var_export($data, true);
		$msg = "url:{$url}\ncurlErrNo:".curl_errno($ch)."\ncurlErr:".curl_error($ch)."\ndata:".$dataDump."\n\n";
		file_put_contents('./log/error_log.fetchProductForPin', $msg, FILE_APPEND);
	}

	curl_close($ch);

	return $data;
}

function initPin() {
	global $autoPinConf;

	$csrftoken = '';
	$sessId = '';
	$try = isset($autoPinConf['try']['initp']) ? (int) $autoPinConf['try']['initp'] : 0;
	$timeout = isset($autoPinConf['timeout']['initp']) ? (int) $autoPinConf['timeout']['initp'] : 60;

	$auth = base64_encode("{$autoPinConf['proxy']['user']}:{$autoPinConf['proxy']['pass']}");
	$context = array(
        'http' => array(
            'proxy' => "{$autoPinConf['proxy']['host']}:{$autoPinConf['proxy']['port']}",
            'request_fulluri' => true,
            'header' => "Proxy-Authorization: Basic $auth",
            'timeout' => $timeout
        )
	);
	stream_context_set_default($context);

	do {
		$headers = get_headers('http://www.pinterest.com', true);
		$try--;
	} while (($headers === false || empty($headers['Set-Cookie'])) && $try > 0);

	if (empty($headers['Set-Cookie'])) {
		$dataDump = print_r($headers, true);
		$contextDump = print_r($context, true);
		$msg = "header:\n".$dataDump."context:\n".$contextDump."\n\n";
		file_put_contents('./log/error_log.initpin', $msg, FILE_APPEND);
		return false;
	}

	foreach ($headers['Set-Cookie'] as $cookieValsStr) {
	    $cookieValsArr = explode(';', $cookieValsStr);
	    foreach ($cookieValsArr as $cookieVal) {
	        if (strpos($cookieVal, 'csrftoken') !== false) {
	            $csrftoken = array_pop(explode('=', $cookieVal));  
	        }
	        if (strpos($cookieVal, '_pinterest_sess') !== false) {
	            $sessId = array_pop(explode('=', $cookieVal));  
	        }
	    }
	}

	if (empty($csrftoken) || empty($sessId)) {
		return false;
	} else {
		return array($csrftoken, $sessId);
	}

}

function loginPin($username, $password, $csrftoken, $sessId) {
	global $autoPinConf;

	$try = isset($autoPinConf['try']['login']) ? (int) $autoPinConf['try']['login'] : 0;
	$timeout = isset($autoPinConf['timeout']['login']) ? (int) $autoPinConf['timeout']['login'] : 60;

	$userInfo = new stdclass();
	$userInfo->options = new stdclass();
	$userInfo->options->username_or_email = $username;
	$userInfo->options->password = $password;
	$userInfo->context = new stdclass();
	$postfields = array(
	    'source_url' => '/login/',
	    'data' => json_encode($userInfo),
	    'module_path' => 'App()>LoginPage()>Login()>Button(class_name=primary, text=Log In, type=submit, size=large)',
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://www.pinterest.com/resource/UserSessionResource/create/');
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    "Origin: https://www.pinterest.com",
	    "X-APP-VERSION: 99ec96c",
	    "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36",
	    "X-NEW-APP" => 1,
	    "Accept: application/json, text/javascript, */*; q=0.01",
	    "X-Requested-With: XMLHttpRequest",
	    "X-CSRFToken: {$csrftoken}",
	    "Referer: https://www.pinterest.com/login/",
	    "Accept-Language: zh-CN,zh;q=0.8",
	));
	curl_setopt($ch, CURLOPT_COOKIE, "csrftoken={$csrftoken}; _pinterest_sess={$sessId};");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
	curl_setopt($ch, CURLOPT_PROXY, "{$autoPinConf['proxy']['host']}:{$autoPinConf['proxy']['port']}");
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$autoPinConf['proxy']['user']}:{$autoPinConf['proxy']['pass']}");
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	do {
		$data = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$try--;
	} while ($httpCode != 200 && $try > 0);

	if ($httpCode != 200) {
		$dataDump = print_r($data, true);
		$msg = "account:{$username}\nhttpCode:{$httpCode}\ncurlErrNo:".curl_errno($ch)."\ncurlErr:".curl_error($ch)."\ndata:".$dataDump."\n\n";
		file_put_contents('./log/error_log.loginpin', $msg, FILE_APPEND);
		curl_close($ch);
		return false;
	}
	curl_close($ch);

	preg_match('/.*Set\-Cookie\:(.*).*/', $data, $matchs);
	$loginedCookie = trim(array_pop($matchs));

	if (!empty($loginedCookie)) {
		$loginedCookie = "csrftoken={$csrftoken}; ".$loginedCookie;
		return $loginedCookie;		
	} else {
		return false;
	}
}

function pinIt($loginedCookie, $csrftoken, $pinParam){
	global $autoPinConf;

	$try = isset($autoPinConf['try']['pinit']) ? (int) $autoPinConf['try']['pinit'] : 0;
	$timeout = isset($autoPinConf['timeout']['pinit']) ? (int) $autoPinConf['timeout']['pinit'] : 60;

	$source_url = urlencode("/pin/create/button/?url={$pinParam->options->link}&media={$pinParam->options->image_url}&description={$pinParam->options->description}");
	$postfields = array(
	    'source_url' => $source_url,
	    'data' => json_encode($pinParam),
	    'module_path' => "App()>PinBookmarklet()>PinCreate()>PinForm(description={$pinParam->options->description}, default_board_id=\"\", show_cancel_button=true, cancel_text=Close, link={$pinParam->options->link}, show_uploader=false, image_url={$pinParam->options->image_url}, is_video=null, heading=Pick a board, pin_it_script_button=true)",  
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'http://www.pinterest.com/resource/PinResource/create/');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    "Origin: http://www.pinterest.com",
	    "X-APP-VERSION: 99ec96c",
	    "X-NEW-APP: 1",
	    "Accept: application/json, text/javascript, */*; q=0.01",
	    "X-Requested-With: XMLHttpRequest",
	    "X-CSRFToken: {$csrftoken}",
	    "Referer: http://www.pinterest.com/pin/create/button/?url=http%3A%2F%2Fwww.jjshouse.com%2FBall-Gown-Strapless-Chapel-Train-Satin-Tulle-Wedding-Dress-With-Ruffle-Lace-Beading-Sequins-002000616-g616%3Fsnsref%3Dpt%26utm_content%3Dpt&media=http%3A%2F%2Fd3bvl598xc7qos.cloudfront.net%2Fupimg%2Fjjshouse%2Fo400%2F90%2F4d%2Fbb1be7dc31f0de42c012369d5948904d.jpg&guid=4BdksRwmEqp8-0&description=Wedding+Dresses+-+%24334.49+-+Ball-Gown+Strapless+Chapel+Train+Satin+Tulle+Wedding+Dress+With+Ruffle+Lace+Beading+Sequins+%28002000616%29+http%3A%2F%2Fjjshouse.com%2FBall-Gown-Strapless-Chapel-Train-Satin-Tulle-Wedding-Dress-With-Ruffle-Lace-Beading-Sequins-002000616-g616%3Fsnsref%3Dpt%26utm_content%3Dpt",
	    "Accept-Language: zh-CN,zh;q=0.8",
	    'Expect:',
	));
	curl_setopt($ch, CURLOPT_COOKIE, $loginedCookie);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
	curl_setopt($ch, CURLOPT_PROXY, "{$autoPinConf['proxy']['host']}:{$autoPinConf['proxy']['port']}");
	curl_setopt($ch, CURLOPT_PROXYUSERPWD, "{$autoPinConf['proxy']['user']}:{$autoPinConf['proxy']['pass']}");
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	do {
		$data = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$try--;
	} while ($httpCode != 200 && $try > 0);

	if ($httpCode != 200) {
		$dataDump = print_r($data, true);
		$msg = "httpCode:{$httpCode}\ncurlErrNo:".curl_errno($ch)."\ncurlErr:".curl_error($ch)."\ndata:".$dataDump."\n\n";
		file_put_contents('./log/error_log.pinit', $msg, FILE_APPEND);
		curl_close($ch);
		return false;
	}
	curl_close($ch);

	return true;
}

// fetch cateogrys 
echo "fetching data from API: {$categoryApi}\n";
$categorys = @json_decode(curlFetch($categoryApi), true);
if ($categorys === false) {
	echo "json decode failed.\n";
	die;
} elseif (empty($categorys)) {
	echo "api return empty.\n";
	die;
} else {
	echo "fetch successed.\n";
}

// fetch featured products
$featuredProducts = array();
$productIdsStr = implode(',', $autoPinConf['featuredProductIds']);
$productsApi = str_replace('#IDS#', $productIdsStr, $productsApiRaw);
echo "fetching featured products from API: {$productsApi}\n";
$productsJson = curlFetch($productsApi);
$jsonDecodeResult = @json_decode($productsJson, true);
if ($jsonDecodeResult === false) {
	echo "json decode failed.\n";
	die;
} elseif (empty($jsonDecodeResult)) {
	echo "api return empty.\n";
} else {
	$featuredProducts = $jsonDecodeResult;
	echo "fetch successed.\n";
}

$accountLoginInfo = array();
$productPinedCount = 0;
$minProductId = $specifiedMinPId;
$limit = 50;
do{
	$productIdsApi = str_replace('#MIN#', $minProductId, $productIdsApiRaw);
	$productIdsApi = str_replace('#LIMIT#', $limit, $productIdsApi);

	echo "fetching data from API: {$productIdsApi}\n";
	$productIdsJson = curlFetch($productIdsApi);
	$productIds = @json_decode($productIdsJson, true);
	if ($productIds === false) {
		echo "json decode failed.\n";
		break;
	} elseif (empty($productIds)) {
		echo "api return empty.\n";
		break;
	} else {
		echo "fetch successed.\n";
	}
	$minProductId = end($productIds);
	reset($productIds);

	$productIdsStr = implode(',', $productIds);
	$productsApi = str_replace('#IDS#', $productIdsStr, $productsApiRaw);

	echo "fetching data from API: {$productsApi}\n";
	$productsJson = curlFetch($productsApi);
	$products = @json_decode($productsJson, true);
	if ($products === false) {
		echo "json decode failed.\n";
		continue;
	} elseif (empty($products)) {
		echo "api return empty.\n";
		continue;
	} else {
		echo "fetch successed.\n";
	}

	foreach ($products as $pid => $prod) {
		$catParentId = $prod['cat_id'] == 2 ? 2 : $categorys[$prod['cat_id']]['parent_id'];
		// version-1 just pin dress
		if (!in_array($catParentId, array(2, 3, 4))) {
			continue;
		}

		// pin repeatedly featured products
		$actualProducts = array();
		$actualProducts[$pid] = $prod;
		$productPinedCount++;
		if ($productPinedCount == 10) {
			$actualProducts = $actualProducts + $featuredProducts;
			$productPinedCount = 0;
		}

		foreach ($actualProducts as $productId => $product) {
			$catParentId = $product['cat_id'] == 2 ? 2 : $categorys[$product['cat_id']]['parent_id'];

			$productUrl = $domainWhole.$product['url'];
			$catName = $categorys[$product['cat_id']]['cat_name'];
			$productTag = str_replace(' ', '', $catName);

			$catNameSingular = $inflector->singularize($catName);
			$productTagSingular = $inflector->singularize($productTag);

			$pinParam = new stdclass();
			$pinParam->options = new stdclass();
			$pinParam->options->description = $product['name'].' '.$productUrl.' '.$catNameSingular.' '.$catName.' '.'#'.$productTagSingular.' '.'#'.$productTag;
			$pinParam->options->link = $productUrl;
			$pinParam->options->image_url = $defaultCdn.$product['goods_thumb'];
			$pinParam->options->method = "button";
			$pinParam->options->is_video = null;
			$pinParam->context = new stdclass();

			foreach ($autoPinConf['accounts'] as $accountId => $account) {
				$username = $account['username'];
				$password = $account['password'];
				$boardId = $account['boards'][$catParentId];

				$pinParam->options->board_id = $boardId;

				echo $username." pin ".$productId." on ".$boardId."\n";
				if (!isset($accountLoginInfo[$accountId])) {
					echo "initing pin\n";
					$initResult = initPin();
					if ($initResult !== false) {
						list($csrftoken, $sessId) = $initResult;
						echo "success\n";
					} else {
						echo "error\n";
						continue;
					}

					echo "logining pin\n";
					$loginResult = loginPin($username, $password, $csrftoken, $sessId);
					if ($loginResult !== false) {
						$loginedCookie = $loginResult;	
						echo "success\n";
					} else {
						echo "error\n";
						continue;
					}

					$accountLoginInfo[$accountId]['csrftoken'] = $csrftoken;
					$accountLoginInfo[$accountId]['sessId'] = $sessId;
					$accountLoginInfo[$accountId]['loginedCookie'] = $loginedCookie;
				} else {
					$csrftoken = $accountLoginInfo[$accountId]['csrftoken'];
					$sessId = $accountLoginInfo[$accountId]['sessId'];
					$loginedCookie = $accountLoginInfo[$accountId]['loginedCookie'];
				}

				echo "pining it\n";
				$result = pinIt($loginedCookie, $csrftoken, $pinParam);
				if ($result !== false) {
					echo "success\n";
				} else {
					echo "error\n";
				}
			}

			// stop some seconds every product
			sleep($pinInterval);
		}
	}

	sleep(1 * $apiFetchInterval);
}while(true);