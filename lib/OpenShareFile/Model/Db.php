<?php

namespace OpenShareFile\Model;

use OpenShareFile\Core\Config;

abstract class Db
{
    static private $conn = null;
    static private $transactions = 0;
    
    static protected function getConn()
    {
        if (self::$conn === null) {
            self::$conn = new \PDO(Config::get('bdd_dsn'), Config::get('bdd_user'), Config::get('bdd_pass'), Config::get('bdd_options'));
            self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        
        return self::$conn;
    }
    
    
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
    
    
    public function beginTransaction()
    {
        if (self::$transactions === 0) {
            $this->loadSql('START TRANSACTION')->execute();
        }
        
        self::$transactions++;
    }
    
    
    public function commit()
    {
        self::$transactions--;
        
        if (self::$transactions === 0) {
            $this->loadSql('COMMIT')->execute();
        }
    }
    
    
    public function rollback()
    {
        self::$transactions--;
        
        if (self::$transactions === 0) {
            $this->loadSql('ROLLBACK')->execute();
        }
    }
    
    
    public function lastInsertId()
    {
        return self::getConn()->lastInsertId();
    }
}