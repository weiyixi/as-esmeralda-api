<?php
	return array(
		'accounts' => array(
			array(
				'username' => "ALLYSONJOSHUA@yahoo.com",
				'password' => "abc2014123",
				'boards' => array(
					2 => '526358343888008956',
					4 => '526358343888008957',
					3 => '526358343888008958',
					5 => '526358343888008984',
					89 => '526358343888008986',
					132 => '526358343888008986',
					133 => '526358343888008986',
					114 => '526358343888008986',
					129 => '526358343888008986',
					84 => '526358343888008987',
				),
			),
		),

		'featuredProductIds' => array(
			// dress
			22658,
			25639,
			22786,
		),

		'proxy' => array(
			'host' => '192.168.1.49',
			'port' => 3446,
			'user' => 'nwu',
			'pass' => 'vv8dpk89',
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