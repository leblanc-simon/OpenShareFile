<?php

namespace OpenShareFile\Extension\Twig;

use OpenShareFile\Core\Config;


/**
 * Asset extension for twig class
 *
 * @package     OpenShareFile\Extension\Twig
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Asset extends \Twig_Extension
{
    /**
     * Define twig functions
     *
     * @return  array   the array with the functions define
     * @access  public
     */
    public function getFunctions()
    {
        return array(
            'asset' => new \Twig_Function_Method($this, 'assetFunction'),
            'img' => new \Twig_Function_Method($this, 'imageFunction'),
            'css' => new \Twig_Function_Method($this, 'stylesheetFunction'),
            'js' => new \Twig_Function_Method($this, 'javascriptFunction'),
        );
    }
    
    
    /**
     * Get the class name
     *
     * @return  string  the class name
     * @access  public
     */
    public function getName()
    {
        return __CLASS__;
    }
    
    
    /**
     * Get the asset URL
     *
     * @param   string  $v  the path to append in the root URL
     * @return  string      the asset URL
     * @access  public
     */
    public function assetFunction($v)
    {
        return $this->getBaseUrl().'/'.$v;
    }
    
    
    /**
     * Get the image URL
     *
     * @param   string  $v  the path to append in the images URL
     * @return  string      the image URL
     * @access  public
     */
    public function imageFunction($v)
    {
        if (preg_match('#^https?::/#', $v)) {
            return $v;
        }

        return $this->assetFunction('img/'.$v);
    }
    
    
    /**
     * Get the CSS URL
     *
     * @param   string  $v  the path to append in the CSS URL
     * @return  string      the CSS URL
     * @access  public
     */
    public function stylesheetFunction($v)
    {
        if (preg_match('#^https?::/#', $v)) {
            return $v;
        }

        return $this->assetFunction('css/'.$v);    
    }
    
    
    /**
     * Get the javascript URL
     *
     * @param   string  $v  the path to append in the javascript URL
     * @return  string      the javascript URL
     * @access  public
     */
    public function javascriptFunction($v)
    {
        if (preg_match('#^https?::/#', $v)) {
            return $v;
        }

        return $this->assetFunction('js/'.$v);
    }
    
    
    /**
     * Get the base URL
     *
     * @return  string      the base URL
     * @access  private
     */
    private function getBaseUrl()
    {
        $root = $_SERVER['DOCUMENT_ROOT'];
        $current = dirname($_SERVER['SCRIPT_FILENAME']);

        return str_replace(array($root, DIRECTORY_SEPARATOR), array('', '/'), $current).'/themes/'.Config::get('theme');
    }
}