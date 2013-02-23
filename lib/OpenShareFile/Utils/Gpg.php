<?php

namespace OpenShareFile\Utils;

use OpenShareFile\Core\Config;
use OpenShareFile\Core\Exception;

/**
 * GPG encrypt/decrypt class
 *
 * @package     OpenShareFile\Utils
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Gpg
{
    /**
     * Check if requirement is OK to use GPG
     *
     * @throws  \OpenShareFile\Core\Exception\Exception     if requirement isn't valid
     * @access  private
     * @static
     */
    static private function check()
    {
        $bin = Config::get('crypt_binary');
        if (file_exists($bin) === false || is_executable($bin) === false) {
            throw new Exception\Exception($bin.' isn\'t a valid executable');
        }
    }
    
    
    /**
     * Encrypt a file with GPG
     *
     * @param   string  $file       The file to encrypt
     * @param   string  $password   The password to use for encrypt file
     * @param   string  $output     The filename where save the encrypt file
     * @return  bool                True if the file is encrypt, false else
     * @throws  \OpenShareFile\Core\Exception\Exception     If the original file doesn't exist
     * @access  public
     * @static
     */
    static public function encrypt($file, $password, $output)
    {
        self::check();
        
        if (file_exists($file) === false) {
            throw new Exception\Exception();
        }
        
        $cmdline  = escapeshellcmd(Config::get('crypt_binary'));
        $cmdline .= ' --batch --no-tty --passphrase-fd 0';
        $cmdline .= ' -o '.escapeshellarg($output);
        $cmdline .= ' -c '.escapeshellarg($file);
        
        $handle = proc_open($cmdline,
                            array(
                                0 => array('pipe', 'r'),
                                1 => array('pipe', 'w'),
                                2 => array('pipe', 'w'),
                            ),
                            $pipes,
                            pathinfo($file, PATHINFO_DIRNAME)
        );
        
        if (is_resource($handle) === true) {
            if (fwrite($pipes[0], $password) === false) {
                throw new Exception\Exception('Unable to write in STDIN');
            }
            
            if (fclose($pipes[0]) === false) {
                throw new Exception\Exception('Unable to close STDIN');
            }
            
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            
            if (proc_close($handle) != 0) {
                throw new Exception\Exception($stderr);
            }
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Decrypt a file with GPG and print it
     *
     * @param   string  $file       The file to decrypt
     * @param   string  $password   The password to use for decrypt file
     * @throws  \OpenShareFile\Core\Exception\Exception     If the encrypt file doesn't exist
     * @throws  \OpenShareFile\Core\Exception\Exception     If the process file pointer can't be open
     * @access  public
     * @static
     */
    static public function decrypt($file, $password)
    {
        self::check();
        
        if (file_exists($file) === false) {
            throw new Exception\Exception();
        }
        
        $cmdline  = escapeshellcmd(Config::get('crypt_binary'));
        $cmdline .= ' --batch --no-tty --passphrase-fd 0 -o -';
        $cmdline .= ' '.escapeshellarg($file);
        
        $handle = proc_open($cmdline,
                            array(
                                0 => array('pipe', 'r'),
                                1 => array('pipe', 'w'),
                                2 => array('pipe', 'w'),
                            ),
                            $pipes,
                            pathinfo($file, PATHINFO_DIRNAME)
        );
        
        if (is_resource($handle) === true) {
            if (fwrite($pipes[0], $password) === false) {
                throw new Exception\Exception('Unable to write in STDIN');
            }
            
            if (fclose($pipes[0]) === false) {
                throw new Exception\Exception('Unable to close STDIN');
            }
            
            $buffer_size = 8192; // send by 8KB : 8192 is the size of the default buffer on many popular operating systems
            while (feof($pipes[1]) === false) {
                echo fread($pipes[1], $buffer_size);
                ob_flush();
                flush();
            }
            fclose($pipes[1]);
            
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            
            if (proc_close($handle) != 0) {
                throw new Exception\Exception($stderr);
            }
        }
    }
}