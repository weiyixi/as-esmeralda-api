<?php

class Autoloader
{
    const NS = 'esmeralda_api\\';
    const SEPARATOR = '\\';
    /**
     * Handles autoloading of classes
     * @param string $className Name of the class to load
     */
    public static function autoload($className)
    {
        if (0 === strpos($className, self::NS, 0)) {
            $fileName = __DIR__ . DIRECTORY_SEPARATOR;
            $fileName .= str_replace(self::SEPARATOR, DIRECTORY_SEPARATOR, $className).'.php';
            require_once $fileName;
        }
    }

}

ini_set('unserialize_callback_func', 'spl_autoload_call');
spl_autoload_register(array(new Autoloader(), 'autoload'));


