<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile\App;

use OpenShareFile\Core\Config as Config;

/**
 * Base controler class
 *
 * @package     OpenShareFile\App
 * @abstract
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
abstract class App
{
    /**
     * Silex Application object
     *
     * @access  protected
     */
    protected $app = null;
    
    /**
     * The title markup of the page
     *
     * @access  public
     */
    public $page_title = '';
    
    /**
     * The public URL's theme to use (JS, CSS, image, ...)
     *
     * @param   public
     */
    public $theme_url = '';
    
    /**
     * The path where is the template file (twig file)
     *
     * @param   public
     */
    public $theme_path = '';
    
    
    /**
     * Controler construtor
     *
     * @param   \Silex\Application  $app    the silex application object
     * @access  public
     */
    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
    }
    
    
    /**
     * Render the twig template for the action
     *
     * @param   string      $name   the name of the template
     * @param   array       $params the parameters to use with the template
     * @return  Response            the response to send to the client
     * @access  protected
     */
    protected function render($name, $params = array())
    {
        return $this->app['twig']->render($this->getClassname().DIRECTORY_SEPARATOR.$name, $params);
    }
    
    
    /**
     * Get the class name without namespace
     *
     * @return  string      the class name without namespace
     * @access  private
     */
    private function getClassname()
    {
        $class_name = get_class($this);
        return str_replace(array(__NAMESPACE__, '\\'), '', $class_name);
    }
}