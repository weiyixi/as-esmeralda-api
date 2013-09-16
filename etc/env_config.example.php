<?php
$siteName = 'JJsHouse';

$siteConf = array(
    'db_host' => "localhost:3306",
    'db_name' => "jjshouse",
    'db_user' => "jjshouse",
    'db_pass' => "jjshouse",

    'category_free_shipping' => array(),
    'goods_free_shipping' => array(),
    'shipping_off_70_percent' => array(
        'start_time' => '2011-07-15 00:00:00', 
        'end_time' => '2020-07-17 23:59:59'
    ),
    'plussize_fee' => 7.99,
    'custom_fee' => date("Ymd") >= 20110404 ? 19.99 : 0,
);

switch($siteName){
case 'JJsHouse':
    $siteConf['rush_order_fee_date'] = array(
        /*
		'within3week' => array(
			'date_start' => 1,
			'date_end' => 14,
			'fee' => 19.99
		),
		'week_3_5' => array(
			'date_start' => 15,
			'date_end' => 21,
			'fee' => 9.99
		)*/
    );
    $siteConf['free_shipping_time'] = array(
        'start_time' => '2000-01-01 00:00:00',
        'end_time' => '2000-01-01 00:00:00',
    );
    break;
case 'JenJenHouse':
    break;
default:
    $siteConf['rush_order_fee_date'] = array(
    );
    $siteConf['free_shipping_time'] = array(
        'start_time' => '2000-01-01 00:00:00',
        'end_time' => '2000-01-01 00:00:00',
    );
}
