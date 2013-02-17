<?php

namespace OpenShareFile\Core;

/**
 *
 */
class Autoload
{
    static public function register()
    {
        spl_autoload_register(__CLASS__.'::autoloader', true);
    }
    
    static public function autoloader($class_name)
    {
        if (strpos($class_name, 'OpenShareFile') !== 0) {
            return;
        }

        $class_name = dirname(__DIR__).DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($class_name, strlen('OpenShareFile'))).'.php';

        require_once $class_name;
    }
}