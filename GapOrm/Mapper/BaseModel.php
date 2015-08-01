<?php
namespace GapOrm\Mapper;

use GapOrm\Exceptions\NoPKException;
use GapOrm\Exceptions\TypeNotExistException;
use GapOrm\Exceptions\WrongFieldClassException;
use GapOrm\GapOrm;

class BaseModel extends OrmMapper
{
    /**
     * Fields types for database
     */
    CONST FIELD_TYPE_BOOL = 1;
    CONST FIELD_TYPE_INT = 2;
    CONST FIELD_TYPE_FLOAT = 3;
    CONST FIELD_TYPE_STR = 4;
    CONST FIELD_TYPE_DATETIME = 5;
    CONST FIELD_TYPE_STR_ARRAY = 6;
    CONST FIELD_TYPE_INT_ARRAY = 7;
    CONST FIELD_TYPE_OBJ = 8;
    /**
     * @var array
     */
    private static $instances = array();
    /**
     * @var array
     */
    private $fields = array();
    /**
     * Get Model instance
     *
     * @param string $className
     * @return mixed
     */
    public static function instance($className = __CLASS__){
        if(!isset(self::$instances[$className]))
            self::$instances[$className] = new $className;

        return self::$instances[$className];
    }
    /**
     * Add Fields
     */
    protected function addField($field)
    {
        if (!$field instanceof FieldMapper)
            throw new WrongFieldClassException();
        $this->fields[] = $field;
    }
    /**
     * Get all fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
    /**
     * Escape
     *
     * @param $str
     * @param bool $quotes
     * @return string
     */
    private function escape($str, $quotes = true)
    {
        return $quotes ? sprintf('%s', $str) : $str;
    }
    /**
     * Check & convert Fields for Select
     *
     * @param $obj
     * @return mixed
     * @throws \GapOrm\Exceptions\TypeNotExistException
     */
    private function convertFromDB($obj)
    {
        foreach ($this->fields as $field) {
            switch ($field->type()) {
                case self::FIELD_TYPE_BOOL :
                    $obj->{$field->identifier()} = (bool) $obj->{$field->identifier()};
                    break;
                case self::FIELD_TYPE_INT :
                    $obj->{$field->identifier()} = (int) $obj->{$field->identifier()};
                    break;
                case self::FIELD_TYPE_FLOAT :
                    $obj->{$field->identifier()} = (float) $obj->{$field->identifier()};
                    break;
                case self::FIELD_TYPE_STR :
                    break;
                case self::FIELD_TYPE_DATETIME :
                    if($obj->{$field->identifier()} === '0')
                        $obj->{$field->identifier()} = false;
                    else{
                        $d = new \DateTime();
                        if(!is_object($obj->{$field->identifier()})){
                            $d->setTimestamp($obj->{$field->identifier()});
                            $obj->{$field->identifier()} = $d;
                        }

                        $obj->{$field->identifier()} = $d;
                    }
                    break;
                case self::FIELD_TYPE_STR_ARRAY :
                    $delimiter = '(^!)';
                    if (empty($obj->{$field->identifier()}))
                        $obj->{$field->identifier()} = array();
                    else
                        $obj->{$field->identifier()} = array_map(function($v) use ($delimiter) {
                            return stripslashes($v);
                        }, explode($delimiter, $obj->{$field->identifier()}));
                    break;
                case self::FIELD_TYPE_INT_ARRAY :
                    $arr = empty($obj->{$field->identifier()})
                        ? array() : explode(',', $obj->{$field->identifier()});
                    $obj->{$field->identifier()} = array_map(function($v) { return (int) str_replace('\'', '', $v); }, $arr);
                    break;
                case self::FIELD_TYPE_OBJ :
                    $obj->{$field->identifier()} = empty($obj->{$field->identifier()})
                        ? new \StdClass() : json_decode($obj->{$field->identifier()});
                    break;
                default :
                    throw new TypeNotExistException();
            }
        }

        return $obj;
    }
    /**
     * Check & convert Fields for Insert, Update
     */
    private function convertToDB($obj)
    {
        $o = $this->getEmptyObject();
        foreach ($this->fields as $field) {
            if (!property_exists($obj, $field->identifier()))
                continue;
            if (is_null($obj->{$field->identifier()})) {
                $o->{$field->identifier()} = 'NULL';
                continue;
            }

            $val = $obj->{$field->identifier()};

            // WARNING: Don not modify $val! it may contain reference
            // to external variable and cause side effects!
            switch ($field->type()) {
                case self::FIELD_TYPE_BOOL :
                    $o->{$field->identifier()} = $val ? 1 : 0;
                    break;
                case self::FIELD_TYPE_INT :
                    $o->{$field->identifier()} = (int) $val;
                    break;
                case self::FIELD_TYPE_FLOAT :
                    $o->{$field->identifier()} = (float) $val;
                    break;
                case self::FIELD_TYPE_STR :
                    $o->{$field->identifier()} = $this->escape($val);
                    break;
                case self::FIELD_TYPE_DATETIME :
                    if($val && !is_null($val))
                        $o->{$field->identifier()} = $val->getTimestamp();
                    else
                        $o->{$field->identifier()} = 0;
                    break;
                case self::FIELD_TYPE_STR_ARRAY :
                    $delimiter = '(^!)';
                    $o->{$field->identifier()} = '"' . implode($delimiter, array_map(function($v) use ($delimiter) {
                            str_replace($delimiter, '', $v);
                        }, $val)) . '"';
                    break;
                case self::FIELD_TYPE_INT_ARRAY :
                    $val = array_map(function($v){ return "'" . (int)$v . "'"; }, $val);
                    $o->{$field->identifier()} = '"' . implode(',', $val) . '"';
                    break;
                case self::FIELD_TYPE_OBJ :
                    $o->{$field->identifier()} = '"' . addslashes(json_encode($val)) . '"';
                    break;
                default :
                    throw new TypeNotExistException();
            }
        }

        return $o;
    }
    /**
     * Create new object
     *
     * @return object
     */
    public function getEmptyObject()
    {
        return new \stdClass();
    }
    /**
     * Get primary key
     *
     * @param bool $require
     * @return mixed
     * @throws NoPkException
     */
    public function getPK($require = true){
        foreach ($this->fields as $field) {
            if ($field->pk()) {
                return $field;
            }
        }

        if ($require)
            throw new NoPKException();
    }
    /**
     * Create Query
     *
     * @param bool $cache
     */
    private function createQuery($cache = false){
        $q = '';
        $i = 0;
        $uniqueArray = array();
        foreach ($this->fields as $field){
            if(in_array($field->identifier(), $uniqueArray))
                $q .= ',' . $field->table() . '.' . $field->identifier() . ' AS ' . $field->table() . ucfirst($field->identifier());
            else{
                if($i > 0)
                    $q .= ',' . $field->table() . '.' . $field->identifier();
                else
                    $q .= 'SELECT ' . $field->table() . '.' . $field->identifier();
            }
            $uniqueArray[] = $field->identifier();
            $i++;
        }

        $q .= ' FROM ' . $this->table();

        if(!$cache){
            foreach ($this->getJoins() as $joinKey => $joinValue){
                $q .= $joinValue;
            }
        }

        $this->setQuery($q);
    }
    /**
     * Get by Primary Key
     *
     * @param $pk
     * @return null
     */
    public function findByPK($pk){
        $this->createQuery();
        $q = $this->getQuery();

        $q .= ' WHERE ' . $this->table() . '.' . $this->getPK()->identifier() . ' = ?';
        $this->setQuery($q);
        $this->setParams($pk);

        GapOrm::getDriver()->query($this->getQuery(), array($pk));
        $obj = GapOrm::getDriver()->selectOnce();

        if(is_null($obj))
            return null;

        $obj = $this->convertFromDB($obj);

        if($obj)
            return $obj;

        return null;
    }
    /**
     * Get data by fieldName
     *
     * @param $fieldName
     * @param array $fieldArray
     * @return array
     */
    public function beginAllInArray($fieldName, $fieldArray = array()){
        $toReturn = array();
        $this->createQuery();

        if(empty($fieldArray))
            return $toReturn;

        $objects = $this->in($fieldName, $fieldArray)->run();

        if(empty($objects))
            return $toReturn;

        foreach($objects as $object)
            $toReturn[$object->{$fieldName}] = $object;

        return $toReturn;
    }
    /**
     * Get all data from model
     *
     * @return array
     */
    public function beginAll(){
        $this->createQuery();

        GapOrm::getDriver()->query($this->getQuery(), array());
        $objects = GapOrm::getDriver()->selectAll();

        if(empty($objects))
            return array();

        $toReturn = array();

        foreach($objects as $object){
            $obj = $this->convertFromDB($object);
            if($obj)
                $toReturn[] = $obj;
        }

        return $toReturn;
    }
    /**
     * Get once result
     *
     * @return object
     */
    public function beginOnce(){
        $this->createQuery();

        GapOrm::getDriver()->query($this->getQuery(), array());
        $obj = GapOrm::getDriver()->selectOnce();

        $obj = $this->convertFromDB($obj);

        if($obj)
            return $obj;

        return null;
    }
    /**
     * Delete record
     *
     * @param $obj
     * @return bool
     */
    public function delete($obj){
        $o = $this->convertToDB($obj);
        $pk = $this->getPK();
        $sql = 'DELETE FROM ' . $this->table() . ' WHERE ' . $pk->identifier() . ' = ?';
        $params = array($o->{$pk->identifier()});

        GapOrm::getDriver()->query($sql, $params);
        $query = GapOrm::getDriver()->delete();

        if($query)
            return true;

        return false;
    }
    /**
     * IN
     *
     * @param $fieldName
     * @param array $fieldArray
     * @return $this
     */
    public function in($fieldName, $fieldArray = array()){
        $fieldArray = array_map(function($v) { return sprintf('"%s"', $v); }, $fieldArray);
        $tableName = '';

        if(strpos($fieldName, '.') === false)
            $tableName = $this->table() . '.';

        if(!empty($fieldArray))
            $this->setIn($tableName . $fieldName . ' IN (' . implode(', ', $fieldArray) . ')');

        return $this;
    }
    /**
     * WHERE
     *
     * @param array $query
     * @param array $differential
     * @return $this
     */
    public function where($query = array(), $differential = array()){
        if(!empty($query)){
            $i = 0;
            $where = $this->getWhere();

            foreach($query as $key => $value){
                $isLike = false; // Is Differential like
                if(isset($differential[$i])){
                    if($differential[$i] == 'like')
                        $isLike = true;
                    $different = $differential[$i];
                }
                else
                    $different = '=';

                // add separator for join tables
                $tableName = '';
                if(strpos($key, '.') === false)
                    $tableName = $this->table() . '.';

                if($i>0){
                    if($isLike)
                        $where .= ' AND ' . $tableName . $key . ' LIKE "%' . $this->escape($value)  . '%"';
                    else
                        $where .= ' AND ' . $tableName . $key . $different . '?';
                }
                else{
                    if($isLike)
                        $where .= ' WHERE ' . $tableName . $key . ' LIKE "%' . $this->escape($value)  . '%"';
                    else
                        $where .= ' WHERE ' . $tableName . $key . $different . '?';
                }
                $i++;
                if(!$isLike)
                    $this->setParams($value);
            }

            $this->setWhere($where);
        }

        return $this;
    }
    /**
     * JOIN
     *
     * @param $tableName
     * @param string $joinType
     * @param $on
     * @param array $joinTableFields
     * @return $this
     */
    public function join($tableName, $joinType = 'left', $on, $joinTableFields = array()){
        switch (strtolower($joinType)){
            case 'left':
                $joinType = ' LEFT JOIN ';
                break;
            case 'right':
                $joinType = ' RIGHT JOIN ';
                break;
            case 'inner':
                $joinType = ' INNER JOIN ';
                break;
            default:
                $joinType = ' LEFT JOIN ';
                break;
        }

        if(!empty($joinTableFields))
            $this->fields = array_merge($this->fields, $joinTableFields);

        $this->setJoins($tableName, $joinType . $tableName . ' ON ' . $on);

        return $this;
    }
    /**
     * ORDER BY
     *
     * @param array $query
     * @return $this
     */
    public function orderBy($query = array()){
        if(!empty($query)){
            $i = 0;
            foreach($query as $key => $value){
                $tableName = '';
                $orderBy = $this->getOrderBy();

                if(strpos($key, '.') === false)
                    $tableName = $this->table() . '.';

                if($i>0)
                    $orderBy .= ', ' . $tableName . $key . ' ' . $value;
                else
                    $orderBy .= ' ORDER BY ' . $tableName . $key . ' ' . $value;

                $this->setOrderBy($orderBy);
                $i++;
            }
        }

        return $this;
    }
    /**
     * GROUP BY
     *
     * @param array $query
     * @return $this
     */
    public function groupBy($query = array()){
        if(!empty($query)){
            $i = 0;
            $groupBy = $this->getOrderBy();

            foreach($query as $value){
                if($i>0)
                    $groupBy .= ', ' . $this->table() . '.' . $value;
                else
                    $groupBy .= ' GROUP BY ' . $this->table() . '.' . $value;

                $i++;
            }

            $this->setGroupBy($groupBy);
        }

        return $this;
    }
    /**
     * LIMIT
     *
     * @param $limit
     * @param bool $startFrom
     * @return $this
     */
    public function limit($limit, $startFrom = false){
        if($startFrom !== false)
            $this->setLimit(' LIMIT ' . $startFrom . ', ' . $limit);
        else
            $this->setLimit(' LIMIT ' . $limit);

        return $this;
    }
    /**
     * RUN
     *
     * @param bool $oneRecord
     * @return array
     */
    public function run($oneRecord = false){
        $this->createQuery(true);
        $query = $this->getQuery();

        foreach ($this->getJoins() as $joinValue){
            $query .= $joinValue;
        }

        if(!is_null($this->getWhere())){
            $query .= $this->getWhere();
            if(!is_null($this->getIn()))
                $query .= ' AND ' . $this->getIn();
        }
        elseif(!is_null($this->getIn()))
            $query .= ' WHERE ' . $this->getIn();

        if(!is_null($this->getGroupBy()))
            $query .= $this->getGroupBy();

        if(!is_null($this->getOrderBy()))
            $query .= $this->getOrderBy();

        if(!is_null($this->getLimit()))
            $query .= $this->getLimit();

        $this->setQuery($query);

        GapOrm::getDriver()->query($query, $this->getParams());

        if($this->isQueryDumpEnabled())
            var_dump($query);

        if($oneRecord){
            $objects = GapOrm::getDriver()->selectOnce();
            if(is_null($objects))
                return null;
        }
        else{
            $objects = GapOrm::getDriver()->selectAll();
            if(empty($objects))
                return array();
        }

        $toReturn = array();

        if($oneRecord)
            return $this->convertFromDB($objects);
        else{
            foreach($objects as $object){
                $obj = $this->convertFromDB($object);
                if($obj)
                    $toReturn[] = $obj;
            }
        }

        return $toReturn;
    }
    /**
     * RUN Once
     *
     * @return array
     */
    public function runOnce(){
        return $this->run(true);
    }
    /**
     * UPDATE & INSERT
     *
     * @param $obj
     * @param array $where
     * @param bool $isUpdate
     * @return bool
     */
    public function save($obj, $where = array(), $isUpdate = false){
        $obj = $this->convertToDB($obj);
        $PK = $this->getPK();
        $params = array();

        if(!empty($where)){
            $i = 0;
            $paramsForSqlString = '';

            foreach ($this->fields as $field) {
                if ($field->noUpdate() || !isset($obj->{$field->identifier()}))
                    continue;
                $params[':' . $field->identifier()] = $obj->{$field->identifier()};

                if($i>0)
                    $paramsForSqlString .= ', ' . $field->identifier() . '=:' . $field->identifier();
                else
                    $paramsForSqlString .= $field->identifier() . '=:' . $field->identifier();

                $i++;
            }

            //where attributes
            $j = 0;
            foreach($where as $key => $value){
                if($j>0)
                    $whereString = ' AND ' . $key . '=:' . $key;
                else
                    $whereString = ' WHERE ' . $key . '=:' . $key;

                $params[':' . $key] = $value;
            }

            $sql = 'UPDATE ' . $this->table() . ' SET ' . $paramsForSqlString . $whereString;

            if($this->isQueryDumpEnabled())
                var_dump($sql);

            $this->setQuery($sql);
            $this->setParams($params);

            GapOrm::getDriver()->query($sql, $params);
            $query = GapOrm::getDriver()->update();

            if($query)
                return $query;

            return false;
        }
        if((property_exists($obj, $PK->identifier()) && $PK->noInsert()) || $isUpdate){
            //Update
            $i = 0;
            $paramsForSqlString = '';

            foreach ($this->fields as $field) {
                if ($field->noUpdate() || !isset($obj->{$field->identifier()}))
                    continue;
                $params[':' . $field->identifier()] = $obj->{$field->identifier()};

                if($i>0)
                    $paramsForSqlString .= ', ' . $field->identifier() . '=:' . $field->identifier();
                else
                    $paramsForSqlString .= $field->identifier() . '=:' . $field->identifier();

                $i++;
            }

            $params[':' . $PK->identifier()] = $obj->{$PK->identifier()};
            $sql = 'UPDATE ' . $this->table() . ' SET ' . $paramsForSqlString . ' WHERE '. $PK->identifier() .'=:'. $PK->identifier();

            if($this->isQueryDumpEnabled())
                var_dump($sql);

            $this->setQuery($sql);
            $this->setParams($params);

            GapOrm::getDriver()->query($sql, $params);
            $query = GapOrm::getDriver()->update();

            if($query)
                return $query;

            return false;
        }
        else{
            //Insert
            $i = 0;
            $paramsForSqlString = '(';
            $valueForSqlString = '(';

            foreach ($this->fields as $field) {
                if ($field->noInsert() || !isset($obj->{$field->identifier()}))
                    continue;
                $params[':' . $field->identifier()] = $obj->{$field->identifier()};

                if($i>0){
                    $paramsForSqlString .= ', ' . $field->identifier();
                    $valueForSqlString  .= ', :' . $field->identifier();
                }
                else{
                    $paramsForSqlString .= $field->identifier();
                    $valueForSqlString  .= ':' . $field->identifier();
                }

                $i++;
            }

            $paramsForSqlString .= ')';
            $valueForSqlString .= ')';
            $sql = 'INSERT INTO ' . $this->table() . ' ' . $paramsForSqlString . ' VALUES ' . $valueForSqlString;

            if($this->isQueryDumpEnabled())
                var_dump($sql);

            $this->setQuery($sql);
            $this->setParams($params);

            GapOrm::getDriver()->query($sql, $params);
            $query = GapOrm::getDriver()->insert();

            if($query)
                return $query;

            return false;
        }
    }
}