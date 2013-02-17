<?php

namespace OpenShareFile\App;

class Index extends App
{
    public function defaultAction()
    {
        return $this->render('default.html.twig');
    }
}