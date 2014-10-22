<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile\Extension\Symfony\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


/**
 * Password constraint validator
 *
 * @package     OpenShareFile\Extension\Symfony\Validator\Constraints
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class PasswordValidator extends ConstraintValidator
{
    /**
     * Method use to validate the constraint
     *
     * @param   mixed   $value  the value to validate
     * @param   \Symfony\Component\Validator\Constraint     $constraint     the constraint to use to validate the value
     * @access  public
     */
    public function validate($value, Constraint $constraint)
    {
        if (password_verify($value, $constraint->object->{$constraint->method}()) === false) {
            $this->context->addViolation($constraint->message, array('{{ value }}' => $value));
        }
    }
}
