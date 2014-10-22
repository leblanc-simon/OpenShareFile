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

use OpenShareFile\Core\Config;


/**
 * Database abtracted class
 *
 * @package     OpenShareFile\Model
 * @abstract
 * @version     1.0.0
 * @license     http://opensource.org/licenses/MIT  MIT
 * @author      Simon Leblanc <contact@leblanc-simon.eu>
 */
abstract class Db
{
    /**
     * The \PDO object
     *
     * @access  private
     * @static
     */
    static private $conn = null;
    
    /**
     * The number of transaction open
     *
     * @access  private
     * @static
     */
    static private $transactions = 0;
    
    
    /**
     * Get the \PDO connection
     *
     * @return  \PDO    the \PDO connection
     * @access  protected
     * @static
     */
    static protected function getConn()
    {
        if (self::$conn === null) {
            self::$conn = new \PDO(Config::get('bdd_dsn'), Config::get('bdd_user'), Config::get('bdd_pass'), Config::get('bdd_options'));
            self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        
        return self::$conn;
    }
    
    
    /**
     * Initialize a query
     *
     * @param   string  $sql    the sql query
     * @param   array   $params the parameters to use with the query
     * @return  \PDOStatement   the prepared statement
     * @access  protected
     */
    protected function loadSql($sql, $params = array())
    {
        $stmt = self::getConn()->prepare((string)$sql);
        
        if (is_array($params) === true && count($params) > 0) {
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value['value'], $value['type']);
            }
        }
        
        return $stmt;
    }
    
    
    /**
     * Begin a SQL transaction
     *
     * @access  public
     */
    public function beginTransaction()
    {
        if (self::$transactions === 0) {
            $this->loadSql('START TRANSACTION')->execute();
        }
        
        self::$transactions++;
    }
    
    
    /**
     * Commit a SQL transaction
     *
     * @access  public
     */
    public function commit()
    {
        self::$transactions--;
        
        if (self::$transactions === 0) {
            $this->loadSql('COMMIT')->execute();
        }
    }
    
    
    /**
     * Rollback a SQL transaction
     *
     * @access  public
     */
    public function rollback()
    {
        self::$transactions--;
        
        if (self::$transactions === 0) {
            $this->loadSql('ROLLBACK')->execute();
        }
    }
    
    
    /**
     * Get the last inserted ID
     *
     * @return  int     The last inserted ID
     * @access  public
     */
    public function lastInsertId()
    {
        return self::getConn()->lastInsertId();
    }
}