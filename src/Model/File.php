<?php
/**
 * This file is part of the OpenShareFile package.
 *
 * (c) Simon Leblanc <contact@leblanc-simon.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenShareFile\Model;


/**
 * File model class
 *
 * @package     OpenShareFile\Model
 * @abstract
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class File extends Db
{
    /**
     * Id of the file
     *
     * @access  private
     */
    private $id = 0;
    
    /**
     * Id of the related upload
     *
     * @access  private
     */
    private $upload_id = 0;
    
    /**
     * Slug of the file
     *
     * @access  private
     */
    private $slug = null;
    
    /**
     * Relative path (related to data_dir) where is stored the file
     *
     * @access  private
     */
    private $file = null;
    
    /**
     * Real filename of the file
     *
     * @access  private
     */
    private $filename = null;
    
    /**
     * Filesize of the file
     *
     * @access  private
     */
    private $filesize = 0;
    
    /**
     * Created date of the file
     *
     * @access  private
     */
    private $created_at = '0000-00-00 00:00:00';
    
    /**
     * Indicate if the file is deleted or not
     *
     * @access  private
     */
    private $is_deleted = false;
    
    
    /**
     * Constructor
     *
     * @param   string  $slug   the slug of the file to load
     * @access  public
     */
    public function __construct($slug = null)
    {
        if ($slug !== null) {
            $this->get($slug);
        }
    }
    
    
    /**
     * Populate the object in loading a record identify by slug
     *
     * @param   string  $slug   the search slug
     * @access  public
     */
    public function get($slug)
    {
        $sql = 'SELECT * FROM file WHERE slug = :slug AND is_deleted = :is_deleted';
        
        $stmt = $this->loadSql($sql, array(
            ':slug' => array('value' => $slug, 'type' => \PDO::PARAM_STR),
            ':is_deleted' => array('value' => false, 'type' => \PDO::PARAM_BOOL)
        ));
        
        $result = $stmt->execute();
        if ($result === true) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row !== false) {
                $this->populate($row);
            }
        }
    }
    
    
    /**
     * Populate the object the an array
     *
     * @param   array   $row    the array to use for populate object
     * @return  $this   for chained method
     * @access  public
     */
    public function populate($row)
    {
        $this->setId($row['id']);
        $this->setUploadId($row['upload_id']);
        $this->setSlug($row['slug']);
        $this->setFile($row['file']);
        $this->setFilename($row['filename']);
        $this->setFilesize($row['filesize']);
        $this->setCreatedAt($row['created_at']);
        $this->setDeleted($row['is_deleted']);
        
        return $this;
    }
    
    
    /**
     * Save the object in the database
     * 
     * @return  $this   for chained method
     * @access  public
     */
    public function save()
    {
        $sql  = 'INSERT INTO file (upload_id, slug, file, filename, filesize, created_at, is_deleted) VALUES ';
        $sql .= '(:upload_id, :slug, :file, :filename, :filesize, :created_at, :is_deleted)';
        
        $this->loadSql($sql, array(
            ':upload_id'    => array('value' => $this->getUploadId(), 'type' => \PDO::PARAM_INT),
            ':slug'         => array('value' => $this->generateSlug(), 'type' => \PDO::PARAM_STR),
            ':file'         => array('value' => $this->generateFile(), 'type' => \PDO::PARAM_STR),
            ':filename'     => array('value' => $this->getFilename(), 'type' => \PDO::PARAM_STR),
            ':filesize'     => array('value' => $this->getFilesize(), 'type' => \PDO::PARAM_INT),
            ':created_at'   => array('value' => $this->getCreatedAt(), 'type' => \PDO::PARAM_STR),
            ':is_deleted'   => array('value' => $this->isDeleted(), 'type' => \PDO::PARAM_BOOL),
        ))->execute();
        
        $this->setId($this->lastInsertId());
        
        return $this;
    }
    
    
    /**
     * Mark the file as deleted
     *
     * @throws  \Exception      if id = 0, we don't update the file
     * @return  $this           for chained method
     * @access  public
     */
    public function markAsDeleted()
    {
        if ($this->getId() === 0) {
            throw new \Exception('Impossible to mark as deleted a file with id = 0');
        }
        
        $sql = 'UPDATE file SET is_deleted = :is_deleted WHERE id = :id';
        $this->loadSql($sql, array(
            ':id'           => array('value' => $this->getId(), 'type' => \PDO::PARAM_INT),
            ':is_deleted'   => array('value' => true, 'type' => \PDO::PARAM_BOOL),
        ))->execute();
        
        $this->setDeleted(true);
        
        return $this;
    }
    
    
    /**
     * Generate a uniq slug
     *
     * @return  string  the slug generated
     * @access  public
     */
    public function generateSlug()
    {
        $this->setSlug(sha1(uniqid($this->getFilename(), true).rand(0, 9999999).$this->getFilename()));
        
        return $this->slug;
    }
    
    
    /**
     * Generate the path of the file
     * 
     * @return  string  the path of the file generated
     * @access  public
     */
    public function generateFile()
    {
        // some filesystem doesn't support a directory with many file
        // store file in three subfolder level
        $path  = DIRECTORY_SEPARATOR.substr($this->slug, 0, 1).DIRECTORY_SEPARATOR.substr($this->slug, 1, 1).DIRECTORY_SEPARATOR.substr($this->slug, 2, 1);
        $path .= DIRECTORY_SEPARATOR.substr($this->slug, 3);
        
        $this->setFile($path);
        
        return $this->file;
    }
    
    
    /**
     * Get the associated upload
     *
     * @return  Upload  the associated upload
     * @access  public
     */
    public function getUpload()
    {
        $upload = new Upload();
        $upload->getById($this->getUploadId());
        
        return $upload;
    }
    
    
    /**
     * Return the id of the file
     *
     * @return  int     the id of the file
     * @access  public
     */
    public function getId() { return (int)$this->id; }
    
    
    /**
     * Return the id of the upload associated to the file
     *
     * @return  int     the id of the upload associated to the file
     * @access  public
     */
    public function getUploadId() { return (int)$this->upload_id; }
    
    
    /**
     * Return the slug of the file
     *
     * @return  string      the slug of the file
     * @access  public
     */
    public function getSlug() { return (string)$this->slug; }
    
    
    /**
     * Return the path of the file
     *
     * @return  string      the path of the file
     * @access  public
     */
    public function getFile() { return (string)$this->file; }
    
    
    /**
     * Return the real filename of the file
     *
     * @return  string      the real filename of the file
     * @access  public
     */
    public function getFilename() { return (string)$this->filename; }
    
    
    /**
     * Return the filesize of the file
     *
     * @return  int      the filesize of the file
     * @access  public
     */
    public function getFilesize() { return (int)$this->filesize; }
    
    
    /**
     * Return the created date of the file
     *
     * @return  string      the created date of the file
     * @access  public
     */
    public function getCreatedAt()
    {
        if ($this->created_at === '0000-00-00 00:00:00') {
            $this->created_at = date('Y-m-d H:i:s');
        }
        
        return (string)$this->created_at;
    }
    
    
    /**
     * Return if the file is deleted or not
     *
     * @return  bool      true if the file is deleted, false else
     * @access  public
     */
    public function isDeleted() { return (bool)$this->is_deleted; }
    
    
    /**
     * Set the id of the file
     *
     * @param   int     $v  the id of the file
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setId($v)
    {
        if (is_numeric($v) === false) {
            throw new \InvalidArgumentException('id must be an integer');
        }
        
        $this->id = (int)$v;
    }
    
    
    /**
     * Set the id of the upload associated to the file
     *
     * @param   int     $v  the id of the upload
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setUploadId($v)
    {
        if (is_numeric($v) === false) {
            throw new \InvalidArgumentException('upload_id must be an integer');
        }
        
        $this->upload_id = (int)$v;
    }
    
    
    /**
     * Set the slug of the file
     *
     * @param   string     $v  the slug of the file
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setSlug($v)
    {
        if (is_string($v) === false || empty($v) === true) {
            throw new \InvalidArgumentException('slug must be a no empty string');
        }
        
        $this->slug = (string)$v;
    }
    
    
    /**
     * Set the path of the file
     *
     * @param   string     $v  the path of the file
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setFile($v)
    {
        if (is_string($v) === false || empty($v) === true) {
            throw new \InvalidArgumentException('file must be a no empty string');
        }
        
        $this->file = (string)$v;
    }
    
    
    /**
     * Set the real filename of the file
     *
     * @param   string     $v  the real filename of the file
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setFilename($v)
    {
        if (is_string($v) === false || empty($v) === true) {
            throw new \InvalidArgumentException('filename must be a no empty string');
        }
        
        $this->filename = (string)$v;
    }
    
    
    /**
     * Set the filesize of the file
     *
     * @param   int     $v  the filesize of the file
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setFilesize($v)
    {
        if (is_numeric($v) === false) {
            throw new \InvalidArgumentException('filesize must be an integer');
        }
        
        $this->filesize = (int)$v;
    }
    
    
    /**
     * Set the created date of the file
     *
     * @param   string|\DateTime     $v  the created date of the file
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setCreatedAt($v)
    {
        if (is_string($v) === true && !preg_match('/^[0-9]{4}-(0[0-9]|1[0-2])-([0-2][0-9]|3[01]) [012][0-9]:[0-5][0-9]:[0-5][0-9]$/', $v)) {
            throw new \InvalidArgumentException('created_at must be a date (string or DateTime');
        } elseif (is_string($v) === false && ($v instanceof \DateTime) === false) {
            throw new \InvalidArgumentException('created_at must be a date (string or DateTime');
        }
        
        if ($v instanceof \DateTime) {
            $v = $v->format('Y-m-d H:i:s');
        }
        
        $this->created_at = $v;
    }
    
    
    /**
     * Set if the file is deleted or not
     *
     * @param   bool     $v  true if the file is deleted, false else
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setDeleted($v)
    {
        if (is_bool($v) === false && in_array($v, array('1', 1, '0', 0), true) === false) {
            throw new \InvalidArgumentException('is_deleted must be a boolean');
        }
        
        if (is_bool($v) === false) {
            $v = (bool)(int)$v;
        }
        
        $this->is_deleted = $v;
    }
}
