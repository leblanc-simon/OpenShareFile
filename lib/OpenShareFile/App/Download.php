<?php

namespace OpenShareFile\App;

use OpenShareFile\Core\Config;
use OpenShareFile\Core\Exception;
use OpenShareFile\Model\File as DBFile;
use OpenShareFile\Model\Upload as DBUpload;
use OpenShareFile\Utils\Passwd;

use Symfony\Component\HttpFoundation\Response;

/**
 * Download controler
 *
 * @package     OpenShareFile\App
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Download extends App
{
    /**
     * Download file action
     *
     * @return  Response
     * @throws  OpenShareFile\Core\Exception\Error404  Error while retrieving Upload object
     * @throws  OpenShareFile\Core\Exception\Security  Password for download are wrong
     * @throws  OpenShareFile\Core\Exception\Exception Error while reading file to download
     * @access  public
     */
    public function defaultAction()
    {
        $slug = null;
        if ('GET' === $this->app['request']->getMethod()) {
            $slug = $this->app['request']->get('slug');
        } else {
            $form = $this->app['request']->get('form');
            if (is_array($form) === true && isset($form['slug']) === true) {
                $slug = $form['slug'];
            }
        }
        
        if (empty($slug) === true) {
            throw new Exception\Error404();
        }
        
        // Get associated upload to slug
        $upload = new DBUpload($slug);
        if ($upload->getId() === 0) {
            throw new Exception\Error404();
        }
        
        // Check if the upload is deleted
        if ($upload->getIsDeleted() === true) {
            throw new Exception\Error404();
        }
        
        
        // Create form to download files
        $form = $this->app['form.factory']->createBuilder('form')
                ->add('slug', 'hidden', array(
                    'data' => $upload->getSlug(),
                    'required' => true,
                ));
        // If upload is protected, get password
        if ($upload->getPasswd() !== '') {
            $form->add('password', 'password', array('required' => true));
        }
        
        $files = $upload->getFiles();
        foreach ($files as $file) {
            $form->add('file_'.$file->getSlug(), 'hidden', array('required' => false));
        }
        
        $form = $form->getForm();
        
        // Process the form if it's a POST request
        if ('POST' === $this->app['request']->getMethod()) {
            $form->bind($this->app['request']);
            
            if ($form->isValid()) {
                $data = $form->getData();
                
                if ($upload->getIsDeleted() === true) {
                    throw new Exception\Error404();
                }
                
                if ($upload->getPasswd() !== '' && Passwd::password_verify($data['password'], $upload->getPasswd()) === false) {
                    throw new Exception\Security();
                }
                
                $file_slug = null;
                foreach ($data as $key => $value) {
                    if (substr($key, 0, 5) === 'file_' && $value === "1") {
                        $file_slug = substr($key, 5);
                        break;
                    }
                }
                
                if ($file_slug === null) {
                    throw new Exception\Error404();
                }
                
                $file = new DBFile($file_slug);
                if ($file->getId() === 0 || $file->getIsDeleted() === true) {
                    throw new Exception\Error404();
                }
                
                $filename = Config::get('data_dir').$file->getFile();
                if (file_exists($filename) === false) {
                    throw new Exception\Error404();
                }
                
                // send file to client
                $handle = fopen($filename, 'rb');
                if ($handle === false) {
                    throw new Exception\Exception();
                }
                
                $response = new Response();
                
                $response->headers->set('Content-Type', 'application/force-download', true);
                $response->headers->set('Content-disposition', 'attachment; filename="'.str_replace('"', '', $file->getFilename()).'"', true);
                $response->headers->set('Content-Transfer-Encoding', 'application/octet-stream', true);
                $response->headers->set('Content-Length', $file->getFilesize(), true);
                $response->headers->set('Pragma', 'no-cache', true);
                $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0, public', true);
                $response->headers->set('Expires', '0', true);
                
                $response->sendHeaders();
                
                while (feof($handle) === false) {
                    echo fread($handle, 1024 * 8);
                    ob_flush();
                    flush();
                }
                die();
            }
        }
        
        return $this->render('confirm.html.twig', array('upload' => $upload, 'files' => $files, 'form' => $form->createView()));
    }
}