<?php

namespace esmeralda_service\base;

class Util{
    public static function gen_order_sn() {
        $a = mt_rand();
        $b = $a << 2;
        $b = $b | 05646543;
        return substr('0000000000' . abs($b), -10);
    }
}
