<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile\Core;

use OpenShareFile\Core\Exception\Routing as RoutingException;
use Silex\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;


/**
 * Routing class
 *
 * @package     OpenShareFile\Core
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Routing
{
    /**
     * The default namespace for the controllers
     *
     * @var string
     */
    const DEFAULT_CONTROLLER_NAMESPACE = '\\OpenShareFile\\Controller\\';

    /**
     * The Silex application
     *
     * @var Application
     */
    private $application;

    /**
     * The array which contains all routes parameters
     *
     * @var array
     */
    private $routes;


    /**
     * Constructor
     *
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->routes = [];
    }


    /**
     * Load the routing
     *
     * @access  public
     */
    public function load()
    {
        $this->loadFiles();
        $this->loadRoutes();
    }


    /**
     * Load the files which define the routing
     *
     * @access  private
     */
    private function loadFiles()
    {
        $finder = new Finder();

        foreach (Config::get('routing_dirs') as $directory) {
            $finder
                ->files()
                ->name('*.yml')
                ->in($directory)
            ;

            foreach ($finder as $file) {
                $resource = $file->getRealPath();
                $this->routes = array_merge($this->routes, Yaml::parse(file_get_contents($resource)));
            }
        }
    }


    /**
     * Load all routes
     *
     * @access  private
     */
    private function loadRoutes()
    {
        foreach ($this->routes as $route_name => $params) {
            $this->loadRoute($route_name, $params);
        }
    }


    /**
     * Load a route into the application
     *
     * @param   string  $name   the route's name
     * @param   array   $params the route's parameters
     * @access  private
     */
    private function loadRoute($name, array $params)
    {
        $this->checkParameters($params);
        $this->normalizeParameters($params);

        $this->application
            ->match($params['url'], function (Application $application) use ($params) {
                $class = new $params['class']($application);
                return $class->{$params['action'].'Action'}();
            })
            ->method($params['method'])
            ->bind($name)
        ;
    }


    /**
     * Normalize route's parameters and set the default values
     *
     * @param   array   $params     the route's parameters
     * @access  private
     */
    private function normalizeParameters(array &$params)
    {
        if (isset($params['method']) === false) {
            $params['method'] = 'GET';
        }

        if (substr($params['class'], 0, 1) !== '\\') {
            $params['class'] = self::DEFAULT_CONTROLLER_NAMESPACE.$params['class'];
        }
    }


    /**
     * Check if all required parameters is setting
     *
     * @param   array   $params     the route's parameters
     * @throws  Exception\Routing   if a required parameter isn't set
     * @access  private
     */
    private function checkParameters(array $params)
    {
        $required_params = [
            'class',
            'action',
            'url',
        ];

        foreach ($required_params as $required) {
            if (isset($params[$required]) === false) {
                throw new RoutingException();
            }
        }
    }
}
