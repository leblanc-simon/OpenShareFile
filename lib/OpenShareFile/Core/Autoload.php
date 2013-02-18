<?php

namespace OpenShareFile\Core;


/**
 * Autoload class
 *
 * @package     OpenShareFile\Core
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Autoload
{
    /**
     * Register the autoload
     *
     * @access  public
     * @static
     */
    static public function register()
    {
        spl_autoload_register(__CLASS__.'::autoloader', true);
    }
    
    
    /**
     * Method allow to autoload a class
     *
     * @param   string  $class_name     The name of the class to autoload
     * @access  public
     * @static
     */
    static public function autoloader($class_name)
    {
        if (strpos($class_name, 'OpenShareFile') !== 0) {
            return;
        }

        $class_name = dirname(__DIR__).DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, substr($class_name, strlen('OpenShareFile'))).'.php';

        require_once $class_name;
    }
}