<?php
$siteConf = array_merge($siteConf, array(
    'custom_size_dress' => array(
        array(
            'name' => 'bust',
            'inchRange' => range(21, 63, 0.5),
            'cmRange' => range(53, 160, 1),
        ),
        array(
            'name' => 'waist',
            'inchRange' => range(20, 63, 0.5),
            'cmRange' => range(51, 160, 1),
        ),
        array(
            'name' => 'hips',
            'inchRange' => range(20, 63, 0.5),
            'cmRange' => range(51, 160, 1),
        ),
        array(
            'name' => 'hollow_to_floor',
            'inchRange' => range(22, 75, 0.5),
            'cmRange' => range(55, 190, 1),
        ),
        array(
            'name' => 'height',
            'inchRange' => range(35, 76, 0.5),
            'cmRange' => range(88, 193, 1),
        ),
    ),

    'custom_size_wrap' => array(
        array(
            'name' => 'shoulder_width',//TODO create page_common_shoulder_width
            'inchRange' => range(9, 28, 0.5),
            'cmRange' => range(23, 69, 1),
        ),
        array(
            'name' => 'shoulder_to_bust',
            'inchRange' => range(5, 18, 0.5),
            'cmRange' => range(15, 45, 1),
        ),
        array(
            'name' => 'armhole',
            'inchRange' => range(9, 28, 0.5),
            'cmRange' => range(23, 69, 1),
        ),
    ),
));
