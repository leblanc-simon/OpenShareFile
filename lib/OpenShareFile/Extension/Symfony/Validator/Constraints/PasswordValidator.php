<?php

namespace OpenShareFile\Extension\Symfony\Validator\Constraints;

use OpenShareFile\Utils\Passwd;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class PasswordValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (Passwd::password_verify($value, $constraint->object->{$constraint->method}()) === false) {
            $this->context->addViolation($constraint->message, array('{{ value }}' => $value));
        }
    }
}
