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
    /**
     * Id of the upload
     *
     * @access  private
     */
    private $id = 0;
    
    /**
     * Slug of the upload
     *
     * @access  private
     */
    private $slug = null;
    
    /**
     * Lifetime of the upload before deleting
     *
     * @access  private
     */
    private $lifetime = 0;
    
    /**
     * Password of the upload
     *
     * @access  private
     */
    private $passwd = null;
    
    /**
     * Indicate if the upload is encrypted or not
     *
     * @access  private
     */
    private $crypt = false;
    
    /**
     * Created date of the upload
     *
     * @access  private
     */
    private $created_at = '0000-00-00 00:00:00';
    
    /**
     * Indicate if the upload is deleted or not
     *
     * @access  private
     */
    private $is_deleted = false;
    
    
    /**
     * Constructor
     *
     * @param   string  $slug   the slug of the upload to load
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
        $sql = 'SELECT * FROM upload WHERE slug = :slug AND is_deleted = :is_deleted';
        
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
     * Populate the object in loading a record identify by id
     *
     * @param   int     $id   the search id
     * @access  public
     */
    public function getById($id)
    {
        $sql = 'SELECT * FROM upload WHERE id = :id AND is_deleted = :is_deleted';
        
        $stmt = $this->loadSql($sql, array(
            ':id' => array('value' => $id, 'type' => \PDO::PARAM_INT),
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
    public function populate(array $row)
    {
        $this->setId($row['id']);
        $this->setSlug($row['slug']);
        $this->setLifetime($row['lifetime']);
        $this->setPasswd($row['passwd']);
        $this->setCrypt($row['crypt']);
        $this->setCreatedAt($row['created_at']);
        $this->setIsDeleted($row['is_deleted']);
        
        return $this;
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
     * Get the expirated uploads
     *
     * @return  \PDOStatement   the statement to parse expirated upload
     * @access  public
     * @static
     */
    static public function getExpirated()
    {
        $upload = new Upload();
        $sql = 'SELECT * FROM upload WHERE ';
        
        $connector = self::getConn()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        if ($connector === 'sqlite' || $connector === 'sqlite2') {
            $sql .= ' (julianday(Date(\'now\')) - julianday(created_at)) > lifetime';
        } else {
            $sql .= ' (TO_DAYS(NOW()) - TO_DAYS(created_at)) > lifetime';
        }
        
        $sql .= ' AND is_deleted = :is_deleted';
        
        $stmt = $upload->loadSql($sql, array(
            ':is_deleted' => array('value' => false, 'type' => \PDO::PARAM_BOOL),
        ));
        
        $result = $stmt->execute();
        if ($result === true) {
            return $stmt;
        }
        
        return false;
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
     * Mark the upload as deleted
     *
     * @throws  \Exception      if id = 0, we don't update the upload
     * @return  $this           for chained method
     * @access  public
     */
    public function markAsDeleted()
    {
        if ($this->getId() === 0) {
            throw new \Exception('Impossible to mark as deleted an upload with id = 0');
        }
        
        $sql = 'UPDATE upload SET is_deleted = :is_deleted WHERE id = :id';
        $result = $this->loadSql($sql, array(
            ':id'           => array('value' => $this->getId(), 'type' => \PDO::PARAM_INT),
            ':is_deleted'   => array('value' => true, 'type' => \PDO::PARAM_BOOL),
        ))->execute();
        
        $this->setIsDeleted(true);
        
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
    
    
    /**
     * Get the id of the upload
     *
     * @return  int     the id of the upload
     * @access  public
     */
    public function getId() { return (int)$this->id; }
    
    
    /**
     * Get the slug of the upload
     *
     * @return  string  the slug of the upload
     * @access  public
     */
    public function getSlug() { return (string)$this->slug; }
    
    
    /**
     * Get the lifetime of the upload
     *
     * @return  int     the lifetime (in days) of the upload
     * @access  public
     */
    public function getLifetime() { return (int)$this->lifetime; }
    
    
    /**
     * Get the password of the upload
     *
     * @return  string     the password of the upload
     * @access  public
     */
    public function getPasswd() { return (string)$this->passwd; }
    
    
    /**
     * Get if the upload is encrypted or not
     *
     * @return  bool     true if the upload is encrypted, false else
     * @access  public
     */
    public function getCrypt() { return (bool)$this->crypt; }
    
    
    /**
     * Get the created date of the upload
     *
     * @return  string  the created date of the upload
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
     * Return if the upload is deleted or not
     *
     * @return  bool      true if the upload is deleted, false else
     * @access  public
     */
    public function getIsDeleted() { return (bool)$this->is_deleted; }
    
    
    
    /**
     * Set the id of the upload
     *
     * @param   int     $v  the id of the upload
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
     * Set the slug of the upload
     *
     * @param   string     $v  the slug of the upload
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
     * Set the lifetime of the upload
     *
     * @param   int     $v  the lifetime (in days) of the upload
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setLifetime($v)
    {
        if (is_numeric($v) === false) {
            throw new \InvalidArgumentException('lifetime must be an integer');
        }
        
        $this->lifetime = (int)$v;
    }
    
    
    /**
     * Set the password of the upload
     *
     * @param   string     $v  the password of the upload
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setPasswd($v)
    {
        if (empty($v) === false && is_string($v) === false) {
            throw new \InvalidArgumentException('passwd must be a no empty string');
        }
        
        $this->passwd = (string)$v;
    }
    
    
    /**
     * Set if the upload is encrypted or not
     *
     * @param   bool     $v  true if the upload is encrypted, false else
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
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
    
    
    /**
     * Set the created date of the upload
     *
     * @param   string|\DateTime     $v  the created date of the upload
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
     * Set if the upload is deleted or not
     *
     * @param   bool     $v  true if the upload is deleted, false else
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
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