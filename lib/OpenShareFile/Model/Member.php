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
 * Member model class
 *
 * @package     OpenShareFile\Model
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
class Member extends Db
{
    /**
     * Id of the member
     *
     * @access  private
     */
    private $id = 0;
    
    /**
     * Email of the member
     *
     * @access  private
     */
    private $email = null;
    
    /**
     * Password of the member
     *
     * @access  private
     */
    private $passwd = null;
    
    /**
     * Pseudo or name of the member
     *
     * @access  private
     */
    private $name = null;
    
    /**
     * Preferences of the member
     *
     * @access  private
     */
    private $prefs = null;
    
    /**
     * Created date of the upload
     *
     * @access  private
     */
    private $created_at = '0000-00-00 00:00:00';
    
    
    /**
     * Constructor
     *
     * @param   string  $email   the email of the member to load
     * @access  public
     */
    public function __construct($email = null)
    {
        if ($email !== null) {
            $this->get($email);
        }
    }
    
    
    /**
     * Populate the object in loading a record identify by email
     *
     * @param   string   $email   the search email
     * @access  public
     */
    public function get($email)
    {
        $sql = 'SELECT * FROM upload WHERE email = :email';
        
        $stmt = $this->loadSql($sql, array(
            ':email' => array('value' => $email, 'type' => \PDO::PARAM_STR),
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
        $this->setEmail($row['email']);
        $this->setPasswd($row['passwd']);
        $this->setName($row['name']);
        $this->setPrefs($row['prefs']);
        $this->setCreatedAt($row['created_at']);
        
        return $this;
    }
    
    
    /**
     * Get all uploads associated with the member
     *
     * @return  array<Upload>     all uploads associated with the member
     * @access  public
     */
    public function getUploads()
    {
        $sql = 'SELECT * FROM upload WHERE member_id = :member_id AND is_deleted = :is_deleted';
        
        $stmt = $this->loadSql($sql, array(
            ':member_id' => array('value' => $this->getId(), 'type' => \PDO::PARAM_INT),
            ':is_deleted' => array('value' => false, 'type' => \PDO::PARAM_BOOL)
        ));
        
        $result = $stmt->execute();
        $uploads = array();
        
        if ($result === true) {
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $upload = new File();
                $upload->populate($row);
                $uploads[] = $upload;
            }
        }
        
        return $uploads;
    }
    
    
    /**
     * Save the object in the database
     * 
     * @return  $this   for chained method
     * @access  public
     */
    public function save()
    {
        if ($this->getId() === 0) {
            return $this->insert();
        } else {
            return $this->update();
        }
    }
    
    
    /**
     * Insert the object in the database
     * 
     * @return  $this   for chained method
     * @access  private
     * @see     this::save()
     */
    private function insert()
    {
        $sql  = 'INSERT INTO member (email, passwd, name, prefs, created_at) VALUES ';
        $sql .= '(:email, :passwd, :name, :prefs, :created_at)';
        
        $result = $this->loadSql($sql, array(
            ':email' => array('value' => $this->generateSlug(), 'type' => \PDO::PARAM_STR),
            ':passwd' => array('value' => $this->getLifetime(), 'type' => \PDO::PARAM_STR),
            ':name' => array('value' => $this->getPasswd(), 'type' => \PDO::PARAM_STR),
            ':prefs' => array('value' => $this->getCrypt(), 'type' => \PDO::PARAM_STR),
            ':created_at' => array('value' => $this->getCreatedAt(), 'type' => \PDO::PARAM_STR),
        ))->execute();
        
        $this->setId($this->lastInsertId());
        
        return $this;
    }
    
    
    /**
     * Update the object in the database
     * 
     * @return  $this   for chained method
     * @access  private
     * @see     this::save()
     */
    private function update()
    {
        $sql  = 'UPDATE member SET
                    email = :email,
                    passwd = :passwd,
                    name = :name,
                    prefs = :prefs
                WHERE id = :id';
        
        $result = $this->loadSql($sql, array(
            ':email' => array('value' => $this->generateSlug(), 'type' => \PDO::PARAM_STR),
            ':passwd' => array('value' => $this->getLifetime(), 'type' => \PDO::PARAM_STR),
            ':name' => array('value' => $this->getPasswd(), 'type' => \PDO::PARAM_STR),
            ':prefs' => array('value' => $this->getCrypt(), 'type' => \PDO::PARAM_STR),
            ':id' => array('value' => $this->getId(), 'type' => \PDO::PARAM_INT),
        ))->execute();
        
        return $this;
    }
    
    
    /**
     * Get the id of the member
     *
     * @return  int     the id of the member
     * @access  public
     */
    public function getId() { return (int)$this->id; }
    
    
    /**
     * Get the email of the member
     *
     * @return  string  the email of the member
     * @access  public
     */
    public function getEmail() { return (string)$this->email; }
    
    
    /**
     * Get the password of the member
     *
     * @return  string     the password of the member
     * @access  public
     */
    public function getPasswd() { return (string)$this->passwd; }
    
    
    /**
     * Get the name of the member
     *
     * @return  string     the name of the member
     * @access  public
     */
    public function getName() { return (string)$this->name; }
    
    
    /**
     * Get the preferences of the member
     *
     * @return  string     the preferences of the member
     * @access  public
     */
    public function getPrefs() { return (string)$this->prefs; }
    
    
    /**
     * Get the created date of the member
     *
     * @return  string  the created date of the member
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
     * Set the id of the member
     *
     * @param   int     $v  the id of the member
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
     * Set the email of the member
     *
     * @param   string     $v  the email of the member
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setEmail($v)
    {
        if (is_string($v) === false || empty($v) === true) {
            throw new \InvalidArgumentException('slug must be a no empty string');
        }
        
        $this->slug = (string)$v;
    }
    
    
    /**
     * Set the password of the member
     *
     * @param   string     $v  the password of the member
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
     * Set the name of the member
     *
     * @param   string     $v  the name of the member
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setName($v)
    {
        if (empty($v) === false && is_string($v) === false) {
            throw new \InvalidArgumentException('passwd must be a no empty string');
        }
        
        $this->name = (string)$v;
    }
    
    
    /**
     * Set the preferences of the member
     *
     * @param   string     $v  the preferences of the member
     * @throws  \InvalidArgumentException   if the type of param isn't valid
     * @access  public
     */
    public function setPrefs($v)
    {
        if (empty($v) === false && is_string($v) === false) {
            throw new \InvalidArgumentException('passwd must be a no empty string');
        }
        
        $this->prefs = (string)$v;
    }
    
    
    /**
     * Set the created date of the member
     *
     * @param   string|\DateTime     $v  the created date of the member
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
}