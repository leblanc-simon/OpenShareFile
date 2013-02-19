<?php

namespace OpenShareFile\Extension\Symfony\Validator\Constraints;

use Symfony\Component\Validator\Constraint;


class Password extends Constraint
{
    public $message = 'Wrong password.';
    
    public $object;
    public $method;
    
    public function getRequiredOptions()
    {
        return array('object', 'method');
    }
}
