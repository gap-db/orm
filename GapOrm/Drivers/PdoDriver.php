<?php
namespace GapOrm\Drivers;

use GapOrm\Exceptions\ConnectionParamsNotExistsException;
use GapOrm\Exceptions\NoConnectionException;
use GapOrm\Exceptions\PDOException;
use Safan\DatabaseManager\Drivers\Pdo\Exceptions\QueryFailedException;

class PdoDriver
{
    /**
     * @var
     */
    protected $dbh;
    /**
     * @var
     */
    protected $sth;
    /**
     * @var
     */
    protected $config;
    /**
     * @var bool
     */
    public $debug = false;
    /**
     * @var
     */
    public $connectionError;
    /**
     * @var array
     */
    private static $instance = array();

    private function __construct(){}

    private function __clone(){}

    /**
     * @return mixed
     */
    public static function getInstance(){
        $class_name = __CLASS__;
        if(!isset(self::$instance[$class_name]) )
            self::$instance[$class_name] = new $class_name();
        return self::$instance[$class_name];
    }

    /**
     * @param array $config
     * @throws ConnectionParamsNotExistsException
     */
    public function setup(array $config){
        $dbHost = isset($config['host']) ? $config['host'] : false;
        $dbName = isset($config['db']) ? $config['db'] : false;
        $dbUser = isset($config['user']) ? $config['user'] : false;
        $dbPass = isset($config['password']) ? $config['password'] : false;

        if(isset($config['debug'])){
            $this->debug = $config['debug'];
        }

        if(!$dbHost || !$dbName || !$dbUser || !$dbPass)
            throw new ConnectionParamsNotExistsException();

        try {
            $this->dbh = new \PDO('mysql:host='.$dbHost.';dbname=' . $dbName, $dbUser, $dbPass, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        }
        catch (PDOException $e) {
            if($this->debug === true){
                echo "\r\n<!-- PDO CONNECTION ERROR: " . $e->getMessage() . "-->\r\n";
            }
            $this->connectionError = "Error!: " . $e->getMessage() . "<br/>";
            $this->dbh = null;
            return;
        }
    }

    /**
     * Query
     */
    public function query($query, $params=array())
    {
        if (is_null($this->dbh))
            throw new NoConnectionException();
        try{
            $this->sth = $this->dbh->prepare($query);
            if($this->sth->execute($params))
                return $this->debug = false;
            return $this->debug = true;
        }
        catch (PDOException $e){
            return false;
        }
    }
    /**
     * Select All
     */
    public function selectAll()
    {
        if(is_null($this->sth))
            throw new QueryFailedException();
        return $this->sth->fetchAll(\PDO::FETCH_OBJ);
    }
    /**
     * Select once
     */
    public function selectOnce()
    {
        if(is_null($this->sth))
            throw new QueryFailedException();
        $result = $this->sth->fetch(\PDO::FETCH_OBJ);
        if($result)
            return $result;
        return null;
    }
    /**
     * Insert
     */
    public function insert()
    {
        if(is_null($this->sth))
            throw new QueryFailedException();
        return ($this->dbh->lastInsertId() > 0) ? $this->dbh->lastInsertId() : false;
    }
    /**
     * Update
     */
    public function update()
    {
        if(is_null($this->sth))
            throw new QueryFailedException();
        if($this->sth->rowCount() > 0)
            return $this->sth->rowCount();
        return false;
    }
    /**
     * Delete
     */
    public function delete()
    {
        if(is_null($this->sth))
            throw new QueryFailedException();
        if($this->debug === true)
            return false;
        return true;
    }
}