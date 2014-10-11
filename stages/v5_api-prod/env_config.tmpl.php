<?php

// initialize error report
ini_set('display_errors', 1);
error_reporting(E_ALL );

// initialize default time zone
date_default_timezone_set('UTC');

// site configure would be used
$siteConf = array(
    // default feature version
    'version' => '1.0',

    // stage environment
    'stage' => 'production',

    // default editing domain
    'domain' => 'Azazie',

    // all domains for editing
    'domainList' => array('Azazie'),

    // common url prefix
    'URL_PREFIX' => 'admin',
);
