<?php
$siteConf = array_merge($siteConf, array(
    'rs_server' => 'http://dev.jjshouse.com:8900/',
    'theme' => 'fashion',

    'cdn' => array(
        'd3bvl598xc7qos.cloudfront.net/upimg/',
        'd3mna48k5fyuxs.cloudfront.net/upimg/',
        'd3piw3jndo3cpw.cloudfront.net/upimg/',
        'd3rdxpzcwpd7j6.cloudfront.net/upimg/',
    ),

    'rush_order_fee_date' => array(
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
    ),
    'free_shipping_time' => array(
        'start_time' => '2000-01-01 00:00:00',
        'end_time' => '2000-01-01 00:00:00',
    ),
    'category_free_shipping' => array(),
    'goods_free_shipping' => array(),
    'shipping_off_70_percent' => array(
        'start_time' => '2011-07-15 00:00:00', 
        'end_time' => '2020-07-17 23:59:59'
    ),
    'plussize_fee' => 7.99,
    'custom_fee' => date("Ymd") >= 20110404 ? 19.99 : 0,
));

