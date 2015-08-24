<?php
/**
 * This file is part of the Safan package.
 *
 * (c) Harut Grigoryan <ceo@safanlab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GapOrm\Mapper;

class OrmMapper
{

    /**
     * For Custom Query
     *
     * @var string
     */
    private $query = '';

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var array
     */
    private $joins = [];

    /**
     * @var
     */
    private $where;

    /**
     * @var
     */
    private $in;

    /**
     * @var
     */
    private $orderBy;

    /**
     * @var
     */
    private $groupBy;

    /**
     * @var
     */
    private $limit;

    /**
     * @var bool
     */
    private $limitStart = false;

    /**
     * @var bool
     */
    private $limitEnd = false;

    /**
     * @var bool
     */
    private $dumpQueryOnce = false;

    /**
     * @return string
     */
    protected function getQuery(){
        return $this->query;
    }

    /**
     * @param $query
     */
    protected function setQuery($query){
        $this->query = $query;
    }

    /**
     * @return array
     */
    protected function getParams(){
        return $this->params;
    }

    /**
     * @param $params
     */
    protected function setParams($params){
        $this->params[] = $params;
    }

    /**
     * @return array
     */
    protected function getJoins(){
        return $this->joins;
    }

    /**
     * @param $join
     *
     * @param $tableName
     * @param $join
     */
    protected function setJoins($tableName, $join){
        $this->joins[$tableName] = $join;
    }

    /**
     * @return mixed
     */
    protected function getWhere(){
        return $this->where;
    }

    /**
     * @param $where
     */
    protected function setWhere($where){
        $this->where = $where;
    }

    /**
     * @return mixed
     */
    protected function getIn(){
        return $this->in;
    }

    /**
     * @param $in
     */
    protected function setIn($in){
        $this->in = $in;
    }

    /**
     * @return mixed
     */
    protected function getOrderBy(){
        return $this->orderBy;
    }

    /**
     * @param $orderBy
     */
    protected function setOrderBy($orderBy){
        $this->orderBy = $orderBy;
    }

    /**
     * @return mixed
     */
    protected function getGroupBy(){
        return $this->groupBy;
    }

    /**
     * @param $groupBy
     */
    protected function setGroupBy($groupBy){
        $this->groupBy = $groupBy;
    }

    /**
     * @return mixed
     */
    protected function getLimit(){
        return $this->limit;
    }

    /**
     * @param $limit
     */
    protected function setLimit($limit){
        $this->limit = $limit;
    }

    /**
     * @return bool
     */
    protected function getLimitStart(){
        return $this->limitStart;
    }

    /**
     * @param $limitStart
     */
    protected function setLimitStart($limitStart){
        $this->limitStart = $limitStart;
    }

    /**
     * @return bool
     */
    protected function getLimitEnd(){
        return $this->limitEnd;
    }

    /**
     * @param $limitEnd
     */
    protected function setLimitEnd($limitEnd){
        $this->limitEnd = $limitEnd;
    }

    /**
     * Set params default value
     */
    protected function clearParams(){
        $this->limit      = null;
        $this->limitStart = false;
        $this->limitEnd   = false;
        $this->where      = null;
        $this->orderBy    = null;
        $this->groupBy    = null;
        $this->in         = null;
        $this->params     = array();
        $this->joins      = array();
        $this->query      = '';
    }

    /**
     * Enable dump for view query
     */
    public function enableQueryDump(){
        $this->dumpQueryOnce = true;
    }

    /**
     * @return bool
     */
    protected function isQueryDumpEnabled(){
        return $this->dumpQueryOnce;
    }
}