<?php

namespace OpenShareFile\Extension\Swift;

use OpenShareFile\Core\Config;
use OpenShareFile\Core\Exception;

class Send
{
    private $app = null;
    private $message = null;
    
    /**
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     */
    public function send()
    {
        $this->app['mailer']->send($this->message);
    }
    
    
    private function getMessage()
    {
        if ($this->message === null) {
            $this->message = \Swift_Message::newInstance();
        }
        
        return $this->message;
    }
    
    
    /**
     *
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
    
    
    private function configureTransportNull()
    {
        return \Swift_NullTransport::newInstance();
    }
    
    
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