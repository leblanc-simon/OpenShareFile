<?php

namespace OpenShareFile\Core;

/**
 *
 */
class Config
{
    static private $datas = array();
    
    /**
     *
     */
    static public function add(array $datas)
    {
        self::$datas = array_merge(self::$datas, $datas);
    }
    
    
    /**
     *
     */
    static public function set($name, $value, $replace = true)
    {
        if ($replace === true || isset(self::$datas[$name]) === false) {
            self::$datas[$name] = $value;
            return true;
        }
        
        return false;
    }
    
    
    /**
     *
     */
    static public function get($name, $default = null)
    {
        return isset(self::$datas[$name]) === false ? $default : self::$datas[$name];
    }
}