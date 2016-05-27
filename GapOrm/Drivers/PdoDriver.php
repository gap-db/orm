<?php
/**
 * This file is part of the Safan package.
 *
 * (c) Harut Grigoryan <ceo@safanlab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GapOrm\Drivers;

use GapOrm\Exceptions\ConnectionParamsNotExistsException;
use GapOrm\Exceptions\NoConnectionException;
use GapOrm\Exceptions\PDOException;
use GapOrm\Exceptions\QueryFailedException;

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
    private static $instance = [];

    private function __construct(){}

    private function __clone(){}

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        $class_name = __CLASS__;

        if (!isset(self::$instance[$class_name]) ) {
            self::$instance[$class_name] = new $class_name();
        }

        return self::$instance[$class_name];
    }

    /**
     * @param array $config
     * @throws ConnectionParamsNotExistsException
     * @return string
     */
    public function setup(array $config)
    {
        $dbHost = isset($config['host']) ? $config['host'] : false;
        $dbName = isset($config['db']) ? $config['db'] : false;
        $dbUser = isset($config['user']) ? $config['user'] : false;
        $dbPass = isset($config['password']) ? $config['password'] : false;

        if (isset($config['debug'])) {
            $this->debug = $config['debug'];
        }

        if (!$dbHost || !$dbName || !$dbUser || !$dbPass) {
            throw new ConnectionParamsNotExistsException();
        }

        try {
            $this->dbh = new \PDO('mysql:host='.$dbHost.';dbname=' . $dbName, $dbUser, $dbPass, [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);
            $this->dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        } catch (PDOException $e) {
            if ($this->debug === true) {
                echo "\r\n<!-- PDO CONNECTION ERROR: " . $e->getMessage() . "-->\r\n";
            }

            $this->connectionError = "Error!: " . $e->getMessage() . "<br/>";
            $this->dbh             = null;

            return $this->connectionError;
        }
    }

    /**
     * Query
     *
     * @param $query
     * @param array $params
     * @return bool
     * @throws \GapOrm\Exceptions\NoConnectionException
     */
    public function query($query, $params = [])
    {
        if (is_null($this->dbh)) {
            throw new NoConnectionException();
        }

        try {
            $this->sth = $this->dbh->prepare($query);

            if ($this->sth->execute($params)) {
                return $this->debug = false;
            }

            return $this->debug = true;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Select All
     *
     * @return mixed
     * @throws \GapOrm\Exceptions\QueryFailedException
     */
    public function selectAll()
    {
        if (is_null($this->sth)) {
            throw new QueryFailedException();
        }

        return $this->sth->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * Select once
     *
     * @return null
     * @throws \GapOrm\Exceptions\QueryFailedException
     */
    public function selectOnce()
    {
        if (is_null($this->sth)) {
            throw new QueryFailedException();
        }

        $result = $this->sth->fetch(\PDO::FETCH_OBJ);

        if ($result) {
            return $result;
        }

        return null;
    }

    /**
     * Insert
     *
     * @return bool
     * @throws \GapOrm\Exceptions\QueryFailedException
     */
    public function insert()
    {
        if (is_null($this->sth)) {
            throw new QueryFailedException();
        }

        return ($this->dbh->lastInsertId() > 0) ? $this->dbh->lastInsertId() : false;
    }

    /**
     * Update
     *
     * @return bool
     * @throws \GapOrm\Exceptions\QueryFailedException
     */
    public function update()
    {
        if (is_null($this->sth)) {
            throw new QueryFailedException();
        }

        if ($this->sth->rowCount() > 0) {
            return $this->sth->rowCount();
        }

        return false;
    }

    /**
     * Delete
     *
     * @return bool
     * @throws \GapOrm\Exceptions\QueryFailedException
     */
    public function delete()
    {
        if (is_null($this->sth)) {
            throw new QueryFailedException();
        }

        if ($this->debug === true) {
            return false;
        }

        return true;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $this->dbh->beginTransaction();
    }

    /**
     * Begin transaction
     */
    public function commit()
    {
        $this->dbh->commit();
    }

    /**
     * Begin transaction
     */
    public function rollBack()
    {
        $this->dbh->rollBack();
    }

    /**
     * This function checks if the table exists in the passed PDO database connection
     *
     * @param $tableName
     * @return boolean - true if table was found, false if not
     * @throws \GapOrm\Exceptions\NoConnectionException
     */
    public function tableExists($tableName)
    {
        if (is_null($this->dbh)) {
            throw new NoConnectionException();
        }

        $mrSql  = "SHOW TABLES LIKE :table_name";
        $mrStmt = $this->dbh->prepare($mrSql);

        // protect from injection attacks
        $mrStmt->bindParam(":table_name", $tableName, \PDO::PARAM_STR);

        $sqlResult = $mrStmt->execute();

        if ($sqlResult) {
            $row = $mrStmt->fetch(\PDO::FETCH_NUM);

            if ($row[0]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create table
     *
     * @param $tableName
     * @param $fieldString
     * @return string
     */
    public function createTable($tableName, $fieldString) {
        try {
            $sql = 'CREATE table ' . $tableName . ' ('. $fieldString . ');';
            $this->dbh->exec($sql);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Create field
     *
     * @param $tableName
     * @param $fieldString
     * @return string
     */
    public function createField($tableName, $fieldString) {
        try {
            $sql = 'ALTER TABLE ' . $tableName . ' ADD ' . $fieldString . ';';
            $this->dbh->exec($sql);
        } catch(PDOException $e) {
            return $e->getMessage();
        }
    }
}