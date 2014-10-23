<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile\Utils;

use OpenShareFile\Model\Upload as DBUpload;
use OpenShareFile\Core\Exception;
use OpenShareFile\Core\Config;

use Symfony\Component\Console\Output\OutputInterface;


/**
 * Clean process class
 *
 * @package     OpenShareFile\Utils
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Clean
{
    /**
     * Process the cleaning
     *
     * @param   \Symfony\Component\Console\Output\OutputInterface   $output     the output command class (use for log)
     * @throw   \OpenShareFile\Core\Exception\Exception                         if process throw exception (PDO exception)
     * @access  public
     * @static
     */
    static public function run(OutputInterface $output = null)
    {
        $upload = new DBUpload();
        $upload->beginTransaction();
        
        try {
            self::writeOutput('<info>Get all expirated upload</info>', $output);
            
            $upload_stmt = DBUpload::getExpirated();
            if ($upload_stmt === false) {
                self::writeOutput('<error>Impossible to get expirated upload</error>', $output);
                throw new Exception\Exception('Impossible to get expirated upload');
            }
            
            while ($row = $upload_stmt->fetch(\PDO::FETCH_ASSOC)) {
                $upload->populate($row);
                
                if ($upload->getId() !== 0) {
                    self::writeOutput('<info>Process upload : '.$upload->getId().'</info>', $output);
                    $tmp_dir = Config::get('data_dir').DIRECTORY_SEPARATOR.'tmp_zip'.DIRECTORY_SEPARATOR.$upload->getSlug();
                    $files = $upload->getFiles();
                    
                    self::writeOutput('<info>Upload has '.count($files).' files</info>', $output);
                    
                    // Delete all files associated with the upload
                    foreach ($files as $file) {
                        self::writeOutput('<info>Process file : '.$file->getId().'</info>', $output);
                        
                        $path = Config::get('data_dir').$file->getFile();
                        $symlink = $tmp_dir.DIRECTORY_SEPARATOR.$file->getFilename();
                        
                        if ($upload->getCrypt() === true) {
                            self::writeOutput('<info>This file is encrypted</info>', $output);
                            $path .= '.gpg';
                            $symlink = null;
                        }
                        
                        
                        if ($symlink !== null && file_exists($symlink) === true) {
                            self::writeOutput('<info>This file has a symlink</info>', $output);
                            if (@unlink($symlink) === false) {
                                self::writeOutput('<error>Impossible to delete symlink : '.$symlink.'</error>', $output);
                            } else {
                                self::writeOutput('<info>Success to delete symlink : '.$symlink.'</info>', $output);
                            }
                        }
                        
                        if (file_exists($path) === true) {
                            if (@unlink($path) === false) {
                                self::writeOutput('<error>Impossible to delete file : '.$path.'</error>', $output);
                            } else {
                                self::writeOutput('<info>Success to delete file : '.$path.'</info>', $output);
                            }
                        }
                        
                        self::writeOutput('<info>Mark file as deleted</info>', $output);
                        $file->markAsDeleted();
                    }
                    
                    // if tmp_dir exists, delete it
                    if (is_dir($tmp_dir) === true) {
                        self::writeOutput('<info>Delete the ZIP temporary folder</info>', $output);
                        $finder = new \Symfony\Component\Finder\Finder();
                        $files = $finder ->files()
                                        ->name('*')
                                        ->depth(0)
                                        ->in($tmp_dir);
                        foreach ($files as $file) {
                            self::writeOutput('<info>The file '.$file.' is still present</info>', $output);
                            if (@unlink($file) === false) {
                                self::writeOutput('<error>Impossible to deleted file : '.$file.'</error>', $output);
                            }
                        }
                        
                        if (@rmdir($tmp_dir) === false) {
                            self::writeOutput('<error>Impossible to delete folder : '.$tmp_dir.'</error>', $output);
                        }
                    }
                    
                    self::writeOutput('<info>Mark upload as deleted</info>', $output);
                    $upload->markAsDeleted();
                } else {
                    self::writeOutput('<error>The upload has id = 0 : '.print_r($row, true).'</error>', $output);
                }
            }
            
            $upload->commit();
            
        } catch (\Exception $e) {
            $upload->rollback();
            self::writeOutput('<error>'.$e->getMessage().'</error>', $output);
            throw $e;
        }
    }
    
    
    /**
     * Write in the standard ouput if available
     *
     * @param   string                                              $text       the string to output
     * @param   \Symfony\Component\Console\Output\OutputInterface   $output     the output command class (use for log)
     * @return  void
     * @access  private
     * @static
     */
    static private function writeOutput($text, OutputInterface $output = null)
    {
        if ($output === null) {
            return;
        }
        
        $output->writeln($text);
    }
}
