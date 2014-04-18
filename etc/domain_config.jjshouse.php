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
	
	'goods_none_size' => array(3911,4054,5747,3849,2531,2337,4236,5745,2516,2334,3808,3848,3850,3851,4152,12528,4137,3853,
									3909,3917,5746,12517,12851,13302,15986,15987,16014,3809,3910,3918,4043,4045,4050,4051,5751,
									11952,12519,12520,12521,12523,12524,12525,12526,12527,12530,12532,12533,12534,12535,12536,
									12538,12540,12541,12542,12543,12544,12545,12546,12547,12549,12550,12551,12552,12553,12554,
									12621,13301,13303,14483,14581,14582,14584,14588,15945,15988,15989,16011,16016,16730,16736,
									16756,16768,16777,16885,16889,16894,16898,16900,16901,16910,16927,16929,16931,16933,16935,
                                    17166,17171,17176,17194,17199,17202,17531,17539,17540,17542,
                                    20435,24283,20438,12516,20432,22577,24285,18675,19550,20433,22590,12531,19542,19543,
                                    19544,19547,22598,24556,17185,18674,18676,18679,19551,20206,20434,22578,22580,22584,
20418,20419,20420,20421,20422,20423,20424,20425,20426,20427,20428,20429,20430,20431,21301,21302,22605,25120,26299,29234,30285),
	'color_chart_ids' => array(4040,36674,36673,40983,40986),

	// ------------- START - use for parent style sort ----------------
	'style_order_group' => array(
		2 => 1,
		4 => 1,
		3 => 1,
		84 => 2,
	),
	'style_order_group_content' => array(
		1 => array(
			'color',
			'bodice color',
			'sash color',
			'skirt color',
			'embroidery color',
			'wrap',
			'size',
		),
		2 => array(
			'color',
			'size',
			'heel type',
		),
	),
	// ------------- END - use for parent style sort ----------------
));