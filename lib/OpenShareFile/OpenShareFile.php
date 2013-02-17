<?php

namespace OpenShareFile;

use OpenShareFile\Core\Exception;

class OpenShareFile
{
    static private $app = null;
    static private $routing = array();
    
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
                    } catch (Exception\Exception $e) {
                        $template = '_exception'.DIRECTORY_SEPARATOR.strtolower(str_replace('OpenShareFile\\Core\\Exception', '', get_class($e))).'.html.twig';
                        return $app['twig']->render($template, array('exception' => $e));
                    }
                })->method($method)
                  ->bind($datas['route']);
            }
        }
        
        // Options
        if (Core\Config::get('debug', false) === true) {
            self::$app['debug'] = true;
        }
        
        self::$app->run();
    }
    
    
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
    
    
    static private function getApp()
    {
        if (self::$app === null) {
            self::$app = new \Silex\Application();
        }
        
        return self::$app;
    }
    
    
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
    
    
    static private function registerProviders()
    {
        self::registerUrlGenerator();
        self::registerSession();
        self::registerTranslation();
        self::registerTwig();
        self::registerForms();
    }
    
    
    static private function registerUrlGenerator()
    {
        self::getApp()->register(new \Silex\Provider\UrlGeneratorServiceProvider());
    }
    
    
    static private function registerSession()
    {
        self::getApp()->register(new \Silex\Provider\SessionServiceProvider());
    }
    
    
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
    
    
    static private function registerTwig()
    {
        self::getApp()->register(new \Silex\Provider\TwigServiceProvider(), array(
            'twig.path' => Core\Config::get('theme_dir').DIRECTORY_SEPARATOR.Core\Config::get('theme'),
            //'twig.options' => array('cache' => Core\Config::get('cache_dir')),
            'twig.options' => array('cache' => false),
        ));
        
        $app = self::getApp();
        
        $app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
                $twig->addExtension(new Extension\Twig\Asset());
                $twig->addExtension(new Extension\Twig\Config());
                return $twig;
        }));
    }
    
    
    static private function registerForms()
    {
        self::getApp()->register(new \Silex\Provider\FormServiceProvider(), array(
            'form.secret' => sha1(__FILE__.Core\Config::get('form_secret')),
        ));
        
        self::getApp()->register(new \Silex\Provider\ValidatorServiceProvider());
    }
}