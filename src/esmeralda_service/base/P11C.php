<?php
namespace esmeralda_service\base;

class P11C{
    public static function proName($code){
        return G11N::$proCode2Name[strtoupper($code)];
    }

    public static function proCode($name){
        return G11N::$proName2Code[strtolower($name)];
    }

    private static $proName2Code = array(
        'jjshouse' => '',
        'jenjenhouse' => 'JE',
        'faucetland' => 'FL',
        'amormoda' => 'AA',
        'jennyjoseph' => 'PH',
        'azazie' => 'ZZ'
    );

    public static $proCode2Name = array(
        'JE' => 'JenJenHouse',
        'FL' => 'FaucetLand',
        'AA' => 'Amormoda',
        'PH' => 'JennyJoseph',
        'ZZ' => 'Azazie',
    );
}
