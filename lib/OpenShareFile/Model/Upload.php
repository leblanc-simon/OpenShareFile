<?php

namespace OpenShareFile\Model;


/**
 * Upload model class
 *
 * @package     OpenShareFile\Model
 * @abstract
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Upload extends Db
{
    private $id = 0;
    private $slug = null;
    private $lifetime = 0;
    private $passwd = null;
    private $crypt = false;
    private $created_at = '0000-00-00 00:00:00';
    private $is_deleted = false;
    
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
        $sql = 'SELECT * FROM upload WHERE slug = :slug AND is_deleted = :is_deleted';
        
        $stmt = $this->loadSql($sql, array(
            ':slug' => array('value' => $slug, 'type' => \PDO::PARAM_STR),
            ':is_deleted' => array('value' => false, 'type' => \PDO::PARAM_BOOL)
        ));
        
        $result = $stmt->execute();
        if ($result === true) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row !== false) {
                $this->setId($row['id']);
                $this->setSlug($row['slug']);
                $this->setLifetime($row['lifetime']);
                $this->setPasswd($row['passwd']);
                $this->setCrypt($row['crypt']);
                $this->setCreatedAt($row['created_at']);
                $this->setIsDeleted($row['is_deleted']);
            }
        }
    }
    
    
    /**
     * Get all files associated with the upload
     *
     * @return  array<File>     all files associated with the upload
     * @access  public
     */
    public function getFiles()
    {
        $sql = 'SELECT * FROM file WHERE upload_id = :upload_id AND is_deleted = :is_deleted';
        
        $stmt = $this->loadSql($sql, array(
            ':upload_id' => array('value' => $this->getId(), 'type' => \PDO::PARAM_INT),
            ':is_deleted' => array('value' => false, 'type' => \PDO::PARAM_BOOL)
        ));
        
        $result = $stmt->execute();
        $files = array();
        
        if ($result === true) {
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $file = new File();
                $file->populate($row);
                $files[] = $file;
            }
        }
        
        return $files;
    }
    
    
    /**
     * Save the object in the database
     * 
     * @return  $this   for chained method
     * @access  public
     */
    public function save()
    {
        $sql  = 'INSERT INTO upload (slug, lifetime, passwd, crypt, created_at, is_deleted) VALUES ';
        $sql .= '(:slug, :lifetime, :passwd, :crypt, :created_at, :is_deleted)';
        
        $result = $this->loadSql($sql, array(
            ':slug' => array('value' => $this->generateSlug(), 'type' => \PDO::PARAM_STR),
            ':lifetime' => array('value' => $this->getLifetime(), 'type' => \PDO::PARAM_INT),
            ':passwd' => array('value' => $this->getPasswd(), 'type' => \PDO::PARAM_STR),
            ':crypt' => array('value' => $this->getCrypt(), 'type' => \PDO::PARAM_BOOL),
            ':created_at' => array('value' => $this->getCreatedAt(), 'type' => \PDO::PARAM_STR),
            ':is_deleted' => array('value' => $this->getIsDeleted(), 'type' => \PDO::PARAM_BOOL),
        ))->execute();
        
        $this->setId($this->lastInsertId());
        
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
        $this->setSlug(sha1(uniqid(rand(0, 9999999), true).rand(0, 9999999).rand(0, 9999999)));
        
        return $this->slug;
    }
    
    
    public function getId() { return (int)$this->id; }
    public function getSlug() { return (string)$this->slug; }
    public function getLifetime() { return (int)$this->lifetime; }
    public function getPasswd() { return (string)$this->passwd; }
    public function getCrypt() { return (bool)$this->crypt; }
    public function getCreatedAt()
    {
        if ($this->created_at === '0000-00-00 00:00:00') {
            $this->created_at = date('Y-m-d H:i:s');
        }
        
        return (string)$this->created_at;
    }
    public function getIsDeleted() { return (bool)$this->is_deleted; }
    
    public function setId($v)
    {
        if (is_numeric($v) === false) {
            throw new \InvalidArgumentException('id must be an integer');
        }
        
        $this->id = (int)$v;
    }
    
    public function setSlug($v)
    {
        if (is_string($v) === false || empty($v) === true) {
            throw new \InvalidArgumentException('slug must be a no empty string');
        }
        
        $this->slug = (string)$v;
    }
    
    public function setLifetime($v)
    {
        if (is_numeric($v) === false) {
            throw new \InvalidArgumentException('lifetime must be an integer');
        }
        
        $this->lifetime = (int)$v;
    }
    
    public function setPasswd($v)
    {
        if (empty($v) === false && is_string($v) === false) {
            throw new \InvalidArgumentException('passwd must be a no empty string');
        }
        
        $this->passwd = (string)$v;
    }
    
    public function setCrypt($v)
    {
        if (is_bool($v) === false && in_array($v, array('1', 1, '0', 0), true) === false) {
            throw new \InvalidArgumentException('crypt must be a boolean');
        }
        
        if (is_bool($v) === false) {
            $v = (bool)(int)$v;
        }
        
        $this->crypt = $v;
    }
    
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
    
    public function setIsDeleted($v)
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