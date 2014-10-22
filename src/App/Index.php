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

use OpenShareFile\Core\Exception;

/**
 * Index controler
 *
 * @package     OpenShareFile\App
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Index extends App
{
    /**
     * Homepage action
     *
     * @return  Response
     * @access  public
     */
    public function defaultAction()
    {
        return $this->render('default.html.twig');
    }
    
    
    /**
     * About action
     *
     * @return  Response
     * @access  public
     */
    public function aboutAction()
    {
        return $this->render('about.html.twig');
    }
    
    
    /**
     * Change language action
     *
     * @throws \OpenShareFile\Core\Exception\Exception  if wanted locale is not in available locales
     * @return  Response
     * @access  public
     */
    public function languageAction()
    {
        $locale = $this->app['request']->get('locale');
        
        if (in_array($locale, $this->app['translator']->available_locales) === false) {
            throw new Exception\Exception('locale is not available');
        }
        
        $this->app['session']->set('locale', $locale);
        
        // Return to the referer if exist, else in the homepage
        return $this->app->redirect($this->app['request']->headers->get('referer', $this->app['url_generator']->generate('homepage')));
    }
}