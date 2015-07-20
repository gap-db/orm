<?php
namespace GapOrm\Profiler;

use GapOrm\Exceptions\ProfilerParamsNotExistsException;

class BaseProfiler
{
    /**
     * @var array
     */
    private $attributes = [
        'type',
        'time',
        'query',
        'queryType',
        'queryParams',
        'result'
    ];

    /**
     * @var array
     */
    private $options = [];

    /**
     * @param $key
     * @return mixed
     * @throws \GapOrm\Exceptions\ProfilerParamsNotExistsException
     */
    public function getOptions($key){
        if(!isset($this->options[$key]) || empty($this->options[$key]))
            throw new ProfilerParamsNotExistsException('Profiler ' . $key . ' params is not exist');

        return $this->options[$key];
    }

    /**
     * @return array
     */
    public function getAllOptions(){
        return $this->options;
    }

    /**
     * @param $key
     * @param $options
     */
    public function setOptions($key, $options){
        $attrObj = $this->convertAttributes($options);

        $this->options[$key] = $attrObj;
    }

    /**
     * @param $options
     * @return \stdClass
     */
    private function convertAttributes($options){
        $attrObj = new \stdClass();
        foreach($this->attributes as $attribute)
            $attrObj->{$attribute} = isset($options[$attribute]) ? $options[$attribute] : null;

        return $attrObj;
    }
}