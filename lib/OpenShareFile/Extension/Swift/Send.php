<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile\Extension\Swift;

use OpenShareFile\Core\Config;
use OpenShareFile\Core\Exception;


/**
 * Send mail class
 *
 * @package     OpenShareFile\Extension\Swift
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Send
{
    /**
     * Silex Application object
     *
     * @access  private
     */
    private $app = null;
    
    /**
     * The \Swift_Message object
     *
     * @access  private
     */
    private $message = null;
    
    
    /**
     * Constructor of the class
     *
     * @param   \Silex\Application  $app    The Silex application object
     * @throws  OpenShareFile\Core\Exception\Exception  if the mailer isn't defined
     * @access  public
     */
    public function __construct(\Silex\Application $app)
    {
        if (isset($app['mailer']) === false || ($app['mailer'] instanceof \Swift_Mailer) === false) {
            throw new Exception\Exception();
        }
        
        $this->app = $app;
        $this->configureTransport();
    }
    
    
    /**
     * Set the subject of the mail
     *
     * @param   string  $v  the subject of the mail
     * @throws  OpenShareFile\Core\Exception\Exception  if the subject isn't a string
     * @return  $this   for chained method
     * @access  public
     */
    public function setSubject($v)
    {
        if (is_string($v) === false) {
            throw new Exception\Exception('Subject must be a string');
        }
        
        $this->getMessage()->setSubject($v);
        return $this;
    }
    
    
    /**
     * Set the sender of the mail
     *
     * @param   string|array  $v  the sender of the mail
     * @throws  OpenShareFile\Core\Exception\Exception  if the sender isn't a string or an array
     * @return  $this   for chained method
     * @access  public
     */
    public function setFrom($v)
    {
        if (is_string($v) === false && is_array($v) === false) {
            throw new Exception\Exception('From must be a string or an array');
        }
        
        if (is_string($v) === true) {
            $v = array($v);
        }
        
        $this->getMessage()->setFrom($v);
        return $this;
    }
    
    
    /**
     * Set the recipient(s) of the mail
     *
     * @param   string  $v  the recipient(s) of the mail
     * @throws  OpenShareFile\Core\Exception\Exception  if the recipient(s) isn't a string or an array
     * @return  $this   for chained method
     * @access  public
     */
    public function setTo($v)
    {
        if (is_string($v) === false && is_array($v) === false) {
            throw new Exception\Exception('To must be a string or an array');
        }
        
        if (is_string($v) === true) {
            $v = array($v);
        }
        
        $this->getMessage()->setTo($v);
        return $this;
    }
    
    
    /**
     * Set the body of the mail
     *
     * @param   string  $v  the body of the mail
     * @throws  OpenShareFile\Core\Exception\Exception  if the body isn't a string
     * @return  $this   for chained method
     * @access  public
     */
    public function setBody($v)
    {
        if (is_string($v) === false) {
            throw new Exception\Exception('Body must be a string');
        }
        
        $this->getMessage()->setBody($v);
        return $this;
    }
    
    
    /**
     * Send the mail
     *
     * @return  bool    true if it's ok, false else
     * @access  public
     */
    public function send()
    {
        return $this->app['mailer']->send($this->message);
    }
    
    
    /**
     * Get the message object
     *
     * @return  \Swift_Message  the message object
     * @access  private
     */ 
    private function getMessage()
    {
        if ($this->message === null) {
            $this->message = \Swift_Message::newInstance();
        }
        
        return $this->message;
    }
    
    
    /**
     * Configure the transport of the mail according to the configuration
     *
     * @throws  OpenShareFile\Core\Exception\Exception  if the value of transport in configuration isn't valid
     * @access  protected
     */
    protected function configureTransport()
    {
        $swiftmailer_transport = Config::get('swiftmailer_transport', 'Swift_Transport_MailTransport');
        
        switch ($swiftmailer_transport) {
            case 'Swift_Transport_NullTransport':
                $transport = $this->configureTransportNull();
                break;
            case 'Swift_Transport_MailTransport':
                $transport = $this->configureTransportMail();
                break;
            case 'Swift_Transport_EsmtpTransport':
                $transport = $this->configureTransportSmtp();
                break;
            case 'Swift_Transport_SendmailTransport':
                $transport = $this->configureTransportSendmail();
                break;
            default:
                throw new Exception\Exception('bad swift mail transport selected');
        }
        
        $this->app['swiftmailer.transport'] = $transport;
    }
    
    
    /**
     * Configure the transport with \Swift_NullTransport
     *
     * @return  \Swift_NullTransport
     * @access  private
     */
    private function configureTransportNull()
    {
        return \Swift_NullTransport::newInstance();
    }
    
    
    /**
     * Configure the transport with \Swift_MailTransport
     *
     * @return  \Swift_MailTransport
     * @access  private
     */
    private function configureTransportMail()
    {
        $options = Config::get('swiftmailer_options', array());
        
        if (isset($options['extra_params']) === true) {
            $transport = \Swift_MailTransport::newInstance($options['extra_params']);
        } else {
            $transport = \Swift_MailTransport::newInstance();
        }
        
        return $transport;
    }
    
    
    /**
     * Configure the transport with \Swift_Transport_EsmtpTransport
     *
     * @return  \Swift_Transport_EsmtpTransport
     * @access  private
     */
    private function configureTransportSmtp()
    {
        $transport = new \Swift_Transport_EsmtpTransport(
            $this->app['swiftmailer.transport.buffer'],
            array($this->app['swiftmailer.transport.authhandler']),
            $this->app['swiftmailer.transport.eventdispatcher']
        );

        $options = $this->app['swiftmailer.options'] = array_replace(array(
            'host'       => 'localhost',
            'port'       => 25,
            'username'   => '',
            'password'   => '',
            'encryption' => null,
            'auth_mode'  => null,
        ), $this->app['swiftmailer.options'], Config::get('swiftmailer_options', array()));
        

        $transport->setHost($options['host']);
        $transport->setPort($options['port']);
        $transport->setEncryption($options['encryption']);
        $transport->setUsername($options['username']);
        $transport->setPassword($options['password']);
        $transport->setAuthMode($options['auth_mode']);

        return $transport;
    }
    
    
    /**
     * Configure the transport with \Swift_SendmailTransport
     *
     * @return  \Swift_SendmailTransport
     * @access  private
     */
    private function configureTransportSendmail()
    {
        $options = Config::get('swiftmailer_options', array());
        
        if (isset($options['command']) === true) {
            $transport = \Swift_SendmailTransport::newInstance($options['command']);
        } else {
            $transport = \Swift_SendmailTransport::newInstance();
        }
        
        return $transport;
    }
}