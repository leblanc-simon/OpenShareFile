<?php

$routing = array(
    'GET' => array(
        '/' => array(
            'class'     => 'Index',
            'method'    => 'defaultAction',
            'route'     => 'homepage',
        ),
        
        '/upload' => array(
            'class'     => 'Upload',
            'method'    => 'formAction',
            'route'     => 'upload_form',
        ),
        
        '/upload-success' => array(
            'class'     => 'Upload',
            'method'    => 'successAction',
            'route'     => 'upload_success',
        ),
        
        '/download/{slug}' => array(
            'class'     => 'Download',
            'method'    => 'defaultAction',
            'route'     => 'download_confirm',
        ),
    ),
    
    'POST' => array(
        '/upload' => array(
            'class'     => 'Upload',
            'method'    => 'formAction',
            'route'     => 'upload_submit',
        ),
        
        '/upload-info' => array(
            'class'     => 'Upload',
            'method'    => 'infoAction',
            'route'     => 'upload_info',
        ),
        
        '/download' => array(
            'class'     => 'Download',
            'method'    => 'defaultAction',
            'route'     => 'download_submit',
        ),
    ),
);