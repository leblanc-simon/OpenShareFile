<?php

namespace OpenShareFile\App;

use OpenShareFile\Core\Config;
use OpenShareFile\Core\Exception;
use OpenShareFile\Model\File as DBFile;
use OpenShareFile\Model\Upload as DBUpload;
use OpenShareFile\Utils\Passwd;

use OpenShareFile\Extension\Symfony\Validator\Constraints\Password as AssertPassword;

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
     * Download file form action
     *
     * @return  Response
     * @throws  OpenShareFile\Core\Exception\Error404  Error while retrieving Upload object
     * @throws  OpenShareFile\Core\Exception\Security  Password for download are wrong
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
            $form->add('password', 'password', array(
                'required' => true,
                'constraints' => array(
                    new AssertPassword(array(
                        'object' => $upload,
                        'method' => 'getPasswd',
                        'message' => $this->app['translator']->trans('Wrong password.'),
                    )),
                )
            ));
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
                
                // Save this allowed upload
                $this->app['session']->set('allowed_upload', array_merge(array($upload->getSlug()), $this->app['session']->get('allowed_upload', array())));
                
                // Redirect in the download file action
                return $this->app->redirect($this->app['url_generator']->generate('download_file', array('slug' => $file_slug)), 301);
            }
        }
        
        return $this->render('confirm.html.twig', array('upload' => $upload, 'files' => $files, 'form' => $form->createView()));
    }
    
    
    /**
     * Download file action
     *
     * @return  Response
     * @throws  OpenShareFile\Core\Exception\Error404  Error while retrieving Upload object
     * @throws  OpenShareFile\Core\Exception\Security  Password for download are wrong
     * @throws  OpenShareFile\Core\Exception\Exception Error while reading file to download
     * @access  public
     * @see     http://programmation-web.net/2012/04/reprendre-le-telechargement-dun-fichier-en-php/
     */
    public function fileAction()
    {
        $file_slug = $this->app['request']->get('slug');
        
        $file = new DBFile($file_slug);
        if ($file->getId() === 0 || $file->getIsDeleted() === true) {
            throw new Exception\Error404();
        }
        
        $upload = $file->getUpload();
        if ($upload->getId() === 0 || $upload->getIsDeleted() === true) {
            throw new Exception\Error404();
        }
        
        if ($upload->getPasswd() !== '' && in_array($upload->getSlug(), $this->app['session']->get('allowed_upload', array())) === false) {
            throw new Exception\Security();
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
        
        if (ini_get("zlib.output_compression")) {
            ini_set("zlib.output_compression", "Off");
        }
        
        $filesize = $file->getFilesize();
        
        $response = new Response();
        
        $response->headers->set('Content-Type', 'application/force-download', true);
        $response->headers->set('Content-disposition', 'attachment; filename="'.str_replace('"', '', $file->getFilename()).'"', true);
        $response->headers->set('Content-Transfer-Encoding', 'application/octet-stream', true);
        $response->headers->set('Pragma', 'no-cache', true);
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0, public', true);
        $response->headers->set('Expires', '0', true);
        
        // Recovery download : define accept-range
        $response->headers->set('Accept-Ranges', 'bytes', true);
        
        // by default: start begin, stop to end :-)
        $begin  = 0;
        $end    = $filesize - 1;
        
        if ($this->app['request']->server->get('HTTP_RANGE', null) !== null) {
            // Check the format of HTTP_RANGE
            if (!preg_match('~bytes=([0-9]+)?-([0-9]+)?(/[0-9]+)?~i', $this->app['request']->server->get('HTTP_RANGE'), $matches)) {
                $response->setStatusCode(416); // Requested Range Not Satisfiable
                $response->send();
                die();
            }
            
            $begin  = empty($matches[1]) === false ? (int)$matches[1] : null;
            $end    = empty($matches[2]) === false ? (int)$matches[2] : $end;
            
            // Check the value of begin and end
            if ((!$begin && !$end)
                ||
                ($end !== null && $end >= $filesize)
                ||
                ($end && $begin && $end < $begin)
            ) {
                $response->setStatusCode(416); // Requested Range Not Satisfiable
                $response->send();
                die();
            }
            
            if ($begin === null) {
                $begin = $filesize - $end;
                $end -= 1;
            }
            
            // Indicate the send of partial content
            $response->setStatusCode(206); // Partial Content
            
            // Indicate the range of data send
            $response->headers->set('Content-Range', $begin.'-'.$end.'/'.$filesize, true);
        }
        
        
        $response->headers->set('Content-Length', $end - $begin + 1, true);
        
        $response->sendHeaders();
        
        // start read to the begin of send
        fseek($handle, $begin);
        
        $remaining_size = $end - $begin + 1;
        $length_to_send = $remaining_size < 8192 ? $remaining_size : 8192; // send by 8KB
        
        while (false !== $datas = fread($handle, $length_to_send)) {
            echo $datas;
            ob_flush();
            flush();
            
            $remaining_size -= $length_to_send;
            
            if ($remaining_size <= 0) {
                break;
            }
            
            if ($remaining_size < $length_to_send) {
                $length_to_send = $remaining_size;
            }
        }
        
        fclose($handle);
        die();
    }
}