<?php
	return array(
		'featuredProductIds' => array(
			// dress
			22658,
			25639,
			22786,
		),

		'proxy' => array(
			'host' => '192.168.1.49',
			'port' => 3448,
			'user' => '',
			'pass' => '',
		),

		// try count of init pin, login ping and pin it
		'try' => array(
			'initp' => 3,
			'login' => 3,
			'pinit' => 3,
		),

		// connect time out (sec, 60 sec by default)
		'timeout' => array(
			'initp' => 60,
			'login' => 60,
			'pinit' => 60,
		),
	);