<?php

namespace OpenShareFile\App;

use OpenShareFile\Core\Config as Config;

abstract class App
{
    protected $app = null;
    
    public $page_title = '';
    public $theme_url = '';
    public $theme_path = '';
    public $content_file = '';
    
    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
    }
    
    
    protected function render($name, $params = array())
    {
        return $this->app['twig']->render($this->getClassname().DIRECTORY_SEPARATOR.$name, $params);
    }
    
    
    private function getClassname()
    {
        $class_name = get_class($this);
        return str_replace(array(__NAMESPACE__, '\\'), '', $class_name);
    }
}