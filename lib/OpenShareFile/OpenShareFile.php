<?php

namespace OpenShareFile;

use OpenShareFile\Core\Exception;

use Symfony\Component\HttpFoundation\Response;


/**
 * Application class
 *
 * @package     OpenShareFile
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class OpenShareFile
{
    /**
     * Silex Application object
     *
     * @access  private
     * @static
     */
    static private $app = null;
    
    /**
     * The array of the routing
     *
     * @access  private
     * @static
     */
    static private $routing = array();
    
    
    /**
     * Run the application
     *
     * @access  public
     * @static
     */
    static public function run()
    {
        // get required files
        self::getRequired();
        
        // register providers
        self::registerProviders();
        
        // get app
        self::getApp();
        $app = self::$app;
        
        // configure routing
        foreach (self::$routing as $method => $params) {
            foreach ($params as $url => $datas) {
                self::checkDatas($datas);
                
                self::$app->match($url, function() use ($app, $datas) {
                    try {
                        $class_name     = __NAMESPACE__.'\\App\\'.$datas['class'];
                        $method_name    = $datas['method'];
                        
                        $controler = new $class_name($app);
                        
                        return $controler->$method_name();
                    } catch (Exception\Security $e) {
                        if (Core\Config::get('debug', false) === true) {
                            throw $e;
                        }
                        
                        return new Response(
                            $app['twig']->render('_exception'.DIRECTORY_SEPARATOR.'security.html.twig', array('exception' => $e)),
                            403
                        );
                    } catch (Exception\Error404 $e) {
                        if (Core\Config::get('debug', false) === true) {
                            throw $e;
                        }
                        
                        return new Response(
                            $app['twig']->render('_exception'.DIRECTORY_SEPARATOR.'error404.html.twig', array('exception' => $e)),
                            404
                        );
                    } catch (Exception\Exception $e) {
                        if (Core\Config::get('debug', false) === true) {
                            throw $e;
                        }
                        
                        return new Response(
                            $app['twig']->render('_exception'.DIRECTORY_SEPARATOR.'exception.html.twig', array('exception' => $e)),
                            500
                        );
                    }
                })->method($method)
                  ->bind($datas['route']);
                  
                
                if (Core\Config::get('debug', false) === false) {
                    self::$app->error(function (\Exception $e, $code) use ($app) {
                        $template = 'exception.html.twig';
                        
                        if ($code === 404) {
                            $template = 'error404.html.twig';
                        } elseif ($code === 403) {
                            $template = 'security.html.twig';
                        }
                        
                        
                        return new Response($app['twig']->render('_exception'.DIRECTORY_SEPARATOR.$template, array('exception' => $e)));
                    });
                }
            }
        }
        
        // Options
        if (Core\Config::get('debug', false) === true) {
            self::$app['debug'] = true;
        }
        
        self::$app->run();
    }
    
    
    /**
     * Load required files
     *
     * @access  private
     * @static
     */
    static private function getRequired()
    {
        // OpenShareFile autoload
        require_once __DIR__.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'Autoload.php';
        Core\Autoload::register();
        
        // vendor autoload
        require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
        
        // configuration
        require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
        
        // routing
        require_once __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'routing.php';
        self::$routing = $routing;
    }
    
    
    /**
     * Get the Silex Application
     *
     * @return  \Silex\Application  the Silex Application object to use
     * @access  private
     * @static
     */
    static private function getApp()
    {
        if (self::$app === null) {
            self::$app = new \Silex\Application();
        }
        
        return self::$app;
    }
    
    
    /**
     * Check the required data to launch the application
     *
     * @param   array   $datas  the datas to check
     * @throws  \InvalidArgumentException   If a data is missing
     * @access  private
     * @static
     */
    static private function checkDatas($datas)
    {
        if (isset($datas['class']) === false) {
            throw new \InvalidArgumentException('class must be defined');
        }
        
        if (isset($datas['method']) === false) {
            throw new \InvalidArgumentException('method must be defined');
        }
        
        if (isset($datas['route']) === false) {
            throw new \InvalidArgumentException('route must be defined');
        }
    }
    
    
    /**
     * Register somes providers
     *
     * @access  private
     * @static
     */
    static private function registerProviders()
    {
        self::registerUrlGenerator();
        self::registerSession();
        self::registerTranslation();
        self::registerTwig();
        self::registerForms();
        self::registerSwift();
    }
    
    
    /**
     * Register URLGenerator provider
     *
     * @access  private
     * @static
     */
    static private function registerUrlGenerator()
    {
        self::getApp()->register(new \Silex\Provider\UrlGeneratorServiceProvider());
    }
    
    
    /**
     * Register Session provider
     *
     * @access  private
     * @static
     */
    static private function registerSession()
    {
        self::getApp()->register(new \Silex\Provider\SessionServiceProvider());
    }
    
    
    /**
     * Register Translation provider
     *
     * @access  private
     * @static
     */
    static private function registerTranslation()
    {
        self::getApp()->register(new \Silex\Provider\TranslationServiceProvider(), array(
            'locale' => Core\Config::get('language'),
            'locale_fallback' => 'en',
        ));
        
        $app = self::getApp();
        
        $app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());
        
            $finder = new \Symfony\Component\Finder\Finder();
        
            $iterator = $finder ->files()
                                ->name('*.yml')
                                ->depth(0)
                                ->in(Core\Config::get('locale_dir'));
        
            foreach ($iterator as $file) {
                $translator->addResource('yaml', $file->getRealpath(), $file->getBasename('.yml'));
            }
        
            return $translator;
        }));
    }
    
    
    /**
     * Register Twig provider
     *
     * @access  private
     * @static
     */
    static private function registerTwig()
    {
        self::getApp()->register(new \Silex\Provider\TwigServiceProvider(), array(
            'twig.path' => Core\Config::get('theme_dir').DIRECTORY_SEPARATOR.Core\Config::get('theme'),
            'twig.options' => array('cache' => Core\Config::get('cache_dir')),
        ));
        
        $app = self::getApp();
        
        $app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
                $twig->addExtension(new Extension\Twig\Asset());
                $twig->addExtension(new Extension\Twig\Config());
                return $twig;
        }));
    }
    
    
    /**
     * Register Forms provider
     *
     * @access  private
     * @static
     */
    static private function registerForms()
    {
        self::getApp()->register(new \Silex\Provider\FormServiceProvider(), array(
            'form.secret' => sha1(__FILE__.Core\Config::get('form_secret')),
        ));
        
        self::getApp()->register(new \Silex\Provider\ValidatorServiceProvider());
    }
    
    
    /**
     * Register SwiftMailer provider
     *
     * @access  private
     * @static
     */
    static private function registerSwift()
    {
        self::getApp()->register(new \Silex\Provider\SwiftmailerServiceProvider());
    }
}