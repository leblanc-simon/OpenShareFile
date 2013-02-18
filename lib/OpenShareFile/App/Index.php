<?php

namespace OpenShareFile\App;

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
}