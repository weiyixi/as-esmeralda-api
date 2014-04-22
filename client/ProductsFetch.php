<?php
/**
 * run in cli: php ProductsFetch.php [any|onsale(default)]
 */

if (isset($_SERVER['HTTP_HOST']))
{
    die('Only runs in CLI mode.');
}

include_once './etc/auth.config.php';
include_once './etc/db.config.php';

$db = new PDO("mysql:host={$db_host};dbname={$db_name}", $db_user, $db_pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

// --------------- global init ---------------
$productStatus = isset($argv[1]) ? $argv[1] : 'onsale';

$categoryApi = 'https://api.opvalue.com/apis/category/en/all';
$productIdsApiRaw = 'https://api.opvalue.com/apis/products/ids/#MIN#:#LIMIT#';
if ($productStatus == 'any') {
	$productIdsApiRaw .= '?status=any';
}
$productsApiRaw = 'https://api.opvalue.com/apis/products/base/#IDS#/jjshouse';
$productsStylesApiRaw = 'https://api.opvalue.com/apis/products/styles/#IDS#';

$interval = 1; // sec
// -------------------------------------------

// ----------------- util --------------------
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
		ob_start();
		var_dump($data);
		$dataDump = ob_get_contents();
		ob_end_clean();
		$msg = "url:{$url}\ncurlErrNo:".curl_errno($ch)."\ncurlErr:".curl_error($ch)."\ndata:".$dataDump."\n\n";
		file_put_contents('log', $msg, FILE_APPEND);
	}

	curl_close($ch);

	return $data;
}

function getTargetStyles($productsStyles) {
	if (empty($productsStyles) || !is_array($productsStyles)) {
		return array();
	}
	
	$targetStyles = array();
	foreach ($productsStyles as $productId=>$styles) {
		foreach ($styles as $style) {
			$styleNameLower = strtolower($style['oname']);
			if (in_array($styleNameLower, array('color', 'size'))) {
				$targetStyles[$productId][$styleNameLower] = '';
				foreach ($style['children'] as $childStyle) {
					$targetStyles[$productId][$styleNameLower] .= $childStyle['value_en'].',';
				}
				$targetStyles[$productId][$styleNameLower] = rtrim($targetStyles[$productId][$styleNameLower], ',');
			}
		}
	}		

	return $targetStyles;
}
// -------------------------------------------

// fetch & insert 
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

$minProductId = 0;
$limit = 50;
do{
	// --------------------------- fetch ----------------------------------
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
	$productsStylesApi = str_replace('#IDS#', $productIdsStr, $productsStylesApiRaw);

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

	echo "fetching data from API: {$productsStylesApi}\n";
	$productsStylesJson = curlFetch($productsStylesApi);
	$productsStyles = @json_decode($productsStylesJson, true);
	if ($productsStyles === false) {
		echo "json decode failed.\n";
	} elseif (empty($productsStyles)) {
		var_dump($productsStyles);die;
		echo "api return empty.\n";
	} else {
		echo "fetch successed.\n";
	}
	$targetStyles = getTargetStyles($productsStyles);
	
	if ($productStatus == 'any') {
		echo "start fetching un-onsale products & styles...\n";
		$onsaleProductsIds = array_keys($products);
		$exceptOSProductsIds = array_diff($productIds, $onsaleProductsIds);
		if (!empty($exceptOSProductsIds)) {
			echo "un-onsale products ids: ".implode(',', $exceptOSProductsIds)."\n";

			$exceptOSProductIdsStr = implode(',', $exceptOSProductsIds);
			$exceptOSProductsApi = str_replace('#IDS#', $exceptOSProductIdsStr, $productsApiRaw)."?status=any";
			$exceptOSProductsStylesApi = str_replace('#IDS#', $exceptOSProductIdsStr, $productsStylesApiRaw)."?status=any";

			$exceptOSProductsJson = curlFetch($exceptOSProductsApi);
			$exceptOSProducts = @json_decode($exceptOSProductsJson, true);
			if ($exceptOSProducts === false) {
				echo "products-json decode failed.\n";
			}

			$exceptOSProductsStylesJson = curlFetch($exceptOSProductsStylesApi);
			$exceptOSProductsStyles = @json_decode($exceptOSProductsStylesJson, true);
			if ($exceptOSProductsStyles === false) {
				echo "styles-json decode failed.\n";
			}
			$exceptOSTargetStyles = getTargetStyles($exceptOSProductsStyles);

			$products += is_array($exceptOSProducts) ? $exceptOSProducts : array();
			$targetStyles += is_array($exceptOSTargetStyles) ? $exceptOSTargetStyles : array();
		
			echo "merge un-onsale with onsale.\n";
		} else {
			echo "no un-onsale products!\n";
		}
	}
	// -------------------------------------------------------------------

	// --------------------------- insert --------------------------------
	$insPlaceholderG = '';
	$insertDataG = array();
	$insPlaceholderC = '';
	$insertDataC = array();
	foreach ($products as $productId=>$product) {
		$insPlaceholderG .= "(?, ?, ?, ?, ?, ?, ?, ?),";
		$insPlaceholderC .= "(?, ?, ?),";
		$goodsColors = isset($targetStyles[$productId]['color']) ? $targetStyles[$productId]['color'] : '';
		$goodsSizes = isset($targetStyles[$productId]['size']) ? $targetStyles[$productId]['size'] : '';
		$categoryId = $product['cat_id'];
		$categoryName = isset($categorys[$categoryId]['cat_name']) ? $categorys[$categoryId]['cat_name'] : '';
		$insertDataG = array_merge($insertDataG, array($productId, $product['shop_price'], $product['is_on_sale'], $product['goods_thumb'], $goodsColors, $goodsSizes, '65545', 'JJsHouse'));
		$insertDataC = array_merge($insertDataC, array($productId, $categoryId, $categoryName));
	}
	$insPlaceholderG = rtrim($insPlaceholderG, ',');
	$insPlaceholderC = rtrim($insPlaceholderC, ',');
	$sqlG = <<<EOSQL
		REPLACE INTO external_goods_attribute(external_goods_id, sales_price, is_shelf, goods_pic, goods_color, goods_size, party_id, party_name)
		VALUES {$insPlaceholderG}
EOSQL;

	$sqlC = <<<EOSQL
		REPLACE INTO external_goods_cat(external_goods_id, cat_id, cat_name)
		VALUES {$insPlaceholderC}
EOSQL;

	try {
		echo "inserting data into db.\n";

		$db->beginTransaction();

		$pstmt = $db->prepare($sqlG);
		$pstmt->execute($insertDataG);
		
		$pstmt = $db->prepare($sqlC);
		$pstmt->execute($insertDataC);

		if ($db->commit()) {
			echo "Insert successed! Products Ids: ".implode(',', array_keys($products))."\n";
		} else {
			echo "Insert failed!\n";
		}
	} catch(\PDOException $e) {
		echo "DB query error: ".$e->getMessage()."\n";
	}
	echo "\n\n";
	// -------------------------------------------------------------------

	sleep(1 * $interval);
}while(true);


