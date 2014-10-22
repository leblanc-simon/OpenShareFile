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

use OpenShareFile\Core\Config;
use OpenShareFile\Core\Exception;
use OpenShareFile\Extension\Swift;
use OpenShareFile\Model\File as DBFile;
use OpenShareFile\Model\Upload as DBUpload;
use OpenShareFile\Utils\Gpg;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Upload controler
 *
 * @package     OpenShareFile\App
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Upload extends App
{
    /**
     * Upload form action
     *
     * @return  Response
     * @access  public
     */
    public function formAction()
    {
        // Create the form
        $form = $this->app['form.factory']->createBuilder('form')
                ->add('file', 'file', array(
                    'label' => $this->app['translator']->trans('Add a file'),
                    'required' => false,
                ))
                ->add('protect', 'checkbox', array(
                    'label' => $this->app['translator']->trans('Protect by password'),
                    'required' => false,
                ))
                ->add('password', 'password', array(
                    'label' => $this->app['translator']->trans('Password'),
                    'required' => false,
                ))
                ->add('send_by_mail', 'checkbox', array(
                    'label' => $this->app['translator']->trans('Send the download link by email'),
                    'required' => false,
                ))
                ->add('email_subject', 'text', array(
                    'label' => $this->app['translator']->trans('Subject'),
                    'required' => false,
                    'data' => $this->app['translator']->trans('default_subject'),
                ))
                ->add('email_message', 'textarea', array(
                    'label' => $this->app['translator']->trans('Message (the download link will be add at the end of the message)'),
                    'required' => false,
                    'data' => $this->app['translator']->trans('default_message'),
                ));
        
        for ($i = 0, $max = Config::get('max_email', 10); $i < $max; $i++) {
            $form->add('email_'.$i, 'email', array(
                'label' => $this->app['translator']->trans('Email'),
                'required' => false,
            ));
        }
        
        if (Config::get('allow_crypt', false) === true) {
            $form->add('crypt', 'checkbox', array(
                'label' => $this->app['translator']->trans('Crypt files in the server (the files will be crypt with your password)'),
                'required' => false,
            ));
        }
        
        $form = $form->getForm();
        
        // Process form
        if ('POST' === $this->app['request']->getMethod()) {
            // Init the slug_upload in session
            $this->app['session']->set('slug_upload', null);
            
            $form->bind($this->app['request']);
            
            if ($form->isValid()) {
                $data = $form->getData();
                
                try {
                    $max_file = Config::get('max_file');
                    
                    if ($max_file > 1 && isset($data['file']) === true && is_array($data['file']) === true) {
                        if (count($data['file']) > $max_file) {
                            throw new Exception\Exception($this->app['translator']->trans('You can add only %max_file% files', array('%max_file%' => $max_file)));
                        }
                        
                        $upload_slug = null;
                        foreach ($data['file'] as $file) {
                            $upload_slug = $this->processFile($file, $data, $upload_slug);
                        }
                        
                    } elseif (isset($data['file']) === true && ($data['file'] instanceof UploadedFile) === true) {
                        $upload_slug = $this->processFile($data['file'], $data, null);
                        
                    } else {
                        throw new Exception\Exception($this->app['translator']->trans('An error occured'));
                    }
                    
                    $this->app['session']->set('slug_upload', $upload_slug);
                    
                    $this->sendEmails($data, $upload_slug);
                    
                    return $this->app->json(array('success' => true, 'url' => $this->app['url_generator']->generate('upload_success')));
                    
                } catch (\Exception $e) {
                    return $this->app->json(array('success' => false, 'message' => $e->getMessage()), 500);
                }
            }
        }
        
        // Render template
        return $this->render('form.html.twig', array('form' => $form->createView()));
    }
    
    
    /**
     * Success uploaded file action
     *
     * @return  Response
     * @throws  OpenShareFile\Core\Exception\Error404   If the upload slug in session is invalid (not exists in database)
     * @access  public
     */
    public function successAction()
    {
        $upload = new DBUpload($this->app['session']->get('slug_upload'));
        if ($upload->getId() === 0) {
            throw new Exception\Error404();
        }
        
        return $this->render('success.html.twig', array('upload' => $upload, 'files' => $upload->getFiles()));
    }
    
    
    /**
     * Process the save of uploaded file
     *
     * @param   \Symfony\Component\HttpFoundation\File\UploadedFile    $upload_file    the file to process
     * @param   array           $data           the form datas
     * @param   string          $upload_slug    the slug of the Upload object related with File object (null for create a new Upload object)
     * @return  string                          the slug of the Upload object related with File object
     * @throws  OpenShareFile\Core\Exception\Exception  Error while writing into database
     * @throws  OpenShareFile\Core\Exception\Exception  Error while get Upload object
     * @access  private
     */
    private function processFile(\Symfony\Component\HttpFoundation\File\UploadedFile $upload_file, array $data, $upload_slug = null)
    {
        $file = new DBFile();
        $file->beginTransaction();
        
        try {
            // Get or create Upload object
            if ($upload_slug === null) {
                $upload = new DBUpload();
                $upload->generateSlug();
                $upload->setLifetime(Config::get('default_lifetime', 7));
                
                if (isset($data['protect']) === true && $data['protect'] === true
                    && isset($data['password']) === true && empty($data['password']) === false
                ) {
                    $upload->setPasswd(password_hash($data['password'], PASSWORD_BCRYPT));
                    
                    // Crypt is allow only if password isn't empty
                    if (Config::get('allow_crypt', false) === true && isset($data['crypt']) === true && $data['crypt'] === true) {
                        $upload->setCrypt(true);
                    }
                }
                
                $upload->save();
                $upload_slug = $upload->getSlug();
            } else {
                $upload = new DBUpload($upload_slug);
                if ($upload->getId() === 0) {
                    throw new Exception\Exception($this->app['translator']->trans('An error occured'));
                }
            }
            
            
            // Create File object
            $file->setUploadId($upload->getId());
            $file->setFilename($upload_file->getClientOriginalName());
            $file->setFilesize($upload_file->getClientSize());
            
            $file->save();
            
            // Save file in file system
            $save_dir = Config::get('data_dir').pathinfo($file->getFile(), PATHINFO_DIRNAME);
            if (@mkdir($save_dir, Config::get('directory_mode', 0755), true) === false) {
                throw new Exception\Exception();
            }
            
            $upload_file->move($save_dir, pathinfo($file->getFile(), PATHINFO_BASENAME));
            @chmod(Config::get('data_dir').$file->getFile(), Config::get('file_mode', 0644));
            
            if ($upload->getCrypt() === true) {
                $original_file = Config::get('data_dir').$file->getFile();
                $encrypt_file = $original_file.'.gpg';
                
                // crypt original file
                if (Gpg::encrypt($original_file, $data['password'], $encrypt_file) === false) {
                    throw new Exception\Exception();
                }
                
                // delete original file
                @unlink($original_file);
                
                // apply the mode in the new file
                @chmod($encrypt_file, Config::get('file_mode', 0644));
            }
            
            $file->commit();
            
        } catch (\Exception $e) {
            $file->rollback();
            
            throw $e;
        }
        
        return $upload_slug;
    }
    
    
    /**
     * Send emails with the download link
     *
     * @param   array   $data   the form datas
     * @param   string          $upload_slug    the slug of the Upload object
     * @return  bool                            true if the emails are send, false else
     * @access  private
     */
    private function sendEmails($data, $upload_slug)
    {
        if (isset($data['send_by_mail']) === false || $data['send_by_mail'] !== true) {
            return false;
        }
        
        for ($i = 0, $max = Config::get('max_email', 10); $i < $max; $i++) {
            if (isset($data['email_'.$i]) === true && empty($data['email_'.$i]) === false) {
                $this->sendEmail($data, $data['email_'.$i], $upload_slug);
            }
        }
    }
    
    
    /**
     * Send email with the download link
     *
     * @param   array   $data   the form datas
     * @param   string  $email  the email address to send the mail
     * @param   string          $upload_slug    the slug of the Upload object
     * @return  bool                            true if the emails are send, false else
     * @access  private
     */
    private function sendEmail($data, $email, $upload_slug)
    {
        $mail = new Swift\Send($this->app);
        
        $from = Config::get('email_from');
        $to = trim($email);
        $subject = (isset($data['email_subject']) === true ? trim($data['email_subject']) : null);
        $message = (isset($data['email_message']) === true ? trim($data['email_message']) : null);
        
        // Check values which use to send mail
        if (empty($from) === true || empty($to) === true
            || empty($subject) === true || empty($message) === true
        ) {
            return false;
        }
        
        // Check if email address is valid
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        
        return $mail->setTo($to)
                ->setFrom($from)
                ->setSubject($subject)
                ->setBody($message."\n".$this->app['url_generator']->generate('download_confirm', array('slug' => $upload_slug), \Symfony\Component\Routing\Generator\UrlGenerator::ABSOLUTE_URL))
                ->send();
    }
}