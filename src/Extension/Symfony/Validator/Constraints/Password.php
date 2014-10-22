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


/**
 * Password constraint
 *
 * @package     OpenShareFile\Extension\Symfony\Validator\Constraints
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Password extends Constraint
{
    /**
     * Message to use when the constraint failed
     *
     * @access  public
     */
    public $message = 'Wrong password.';
    
    
    /**
     * [Option] : the object to use to the constraint
     *
     * @access  public
     */
    public $object;
    
    /**
     * [Option] : the method to use to the constraint
     *
     * @access  public
     */
    public $method;
    
    
    /**
     * Declare the required options
     *
     * @return  array   an array with the name of the required options
     * @access  public
     */
    public function getRequiredOptions()
    {
        return array('object', 'method');
    }
}
