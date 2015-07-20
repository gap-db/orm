<?php
namespace GapOrm\Mapper;

use Safan\Safan;

class Model extends BaseModel
{
    /**
     * @var array
     */
    private static $instances = array();

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
     * Get by Primary Key
     *
     * @param $pk
     * @return null
     */
    public function findByPK($pk){
        $this->startProfiling();

        $result = parent::findByPK($pk);

        $this->endProfiling('findByPK', $result);
        $this->clearParams();

        return $result;
    }

    /**
     * Get data by fieldName
     *
     * @param $fieldName
     * @param array $fieldArray
     * @return array
     */
    public function beginAllInArray($fieldName, $fieldArray = array()){
        $this->startProfiling();

        $result = parent::beginAllInArray($fieldName, $fieldArray);

        $this->endProfiling('beginAllInArray', $result);
        $this->clearParams();

        return $result;
    }

    /**
     * Get all data from model
     *
     * @return array
     */
    public function beginAll(){
        $this->startProfiling();

        $result = parent::beginAll();

        $this->endProfiling('beginAllInArray', $result);
        $this->clearParams();

        return $result;
    }

    /**
     * Get once result
     *
     * @return object
     */
    public function beginOnce(){
        $this->startProfiling();

        $result = parent::beginOnce();

        $this->endProfiling('beginOnce', $result);
        $this->clearParams();

        return $result;
    }

    /**
     * Delete record
     *
     * @param $obj
     * @return bool
     */
    public function delete($obj){
        $this->startProfiling();

        $result = parent::delete($obj);

        $this->endProfiling('delete', $result);
        $this->clearParams();

        return $result;
    }

    /**
     * RUN
     *
     * @param bool $oneRecord
     * @return array
     */
    public function run($oneRecord = false){
        $this->startProfiling();

        $result = parent::run($oneRecord);

        $this->endProfiling('selectAll', $result);
        $this->clearParams();

        return $result;
    }

    /**
     * RUN Once
     *
     * @return array
     */
    public function runOnce(){
        $this->startProfiling();

        $result = parent::run(true);

        $this->endProfiling('selectOnce', $result);
        $this->clearParams();

        return $result;
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
        $this->startProfiling();

        $result = parent::save($obj, $where, $isUpdate);

        $this->endProfiling('save', $result);
        $this->clearParams();

        return $result;
    }

    /**
     * Start profiling
     */
    private function startProfiling(){
        $profiler = Safan::handler()->getObjectManager()->get('gapOrmProfiler');
        $profiler->getTimer()->start();
    }

    /**
     * @param $profilerType
     * @param $result
     */
    private function endProfiling($profilerType, $result){
        $profiler = Safan::handler()->getObjectManager()->get('gapOrmProfiler');

        $profileOptions = [
            'type'        => 'query',
            'time'        => $profiler->getTimer()->calculate(),
            'query'       => $this->getQuery(),
            'queryType'   => $profilerType,
            'queryParams' => $this->getParams(),
            'result'      => $result
        ];

        $profiler->setOptions($profilerType . '_' . time(), $profileOptions);
    }
}