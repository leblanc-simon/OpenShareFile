<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile;

use OpenShareFile\Core\Exception;
use OpenShareFile\Core\Routing;

use Silex\Application;
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
     * @var     Application
     * @access  private
     * @static
     */
    static private $app = null;
    
    
    /**
     * Run the application
     *
     * @access  public
     * @static
     */
    static public function run()
    {
        // register providers
        self::registerProviders();
        
        // get app
        self::getApp();
        $app = self::$app;
        
        // configure routing
        self::loadRouting();
        
        // Options
        if (Core\Config::get('debug', false) === true) {
            self::$app['debug'] = true;
        }
        
        self::$app->run();
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
            self::$app = new Application();
        }
        
        return self::$app;
    }


    /**
     * Load routing
     */
    static private function loadRouting()
    {
        $routing = new Routing(self::$app);
        $routing->load();

        self::handleErrors();
    }


    /**
     * Setting the error handler
     */
    static private function handleErrors()
    {
        if (Core\Config::get('debug', false) === true) {
            return;
        }

        $app = self::$app;

        $app->error(function (Exception\Security $e) use ($app) {
            return new Response(
                $app['twig']->render('_exception/security.html.twig', array('exception' => $e)),
                403
            );
        });

        $app->error(function (Exception\Error404 $e) use ($app) {
            return new Response(
                $app['twig']->render('_exception/error404.html.twig', array('exception' => $e)),
                404
            );
        });

        $app->error(function (\Exception $e) use ($app) {
            return new Response(
                $app['twig']->render('_exception/exception.html.twig', array('exception' => $e)),
                500
            );
        });
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
            
            $locales = array();
            foreach ($iterator as $file) {
                $translator->addResource('yaml', $file->getRealpath(), $file->getBasename('.yml'));
                $locales[] = $file->getBasename('.yml');
            }
            
            $locale = $app['session']->get('locale', $app['request']->getPreferredLanguage($locales));
            $translator->setLocale($locale);
            $translator->available_locales = $locales;
        
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