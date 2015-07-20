<?php
namespace GapOrm\Profiler\Tools;

class Timer
{
    /**
     * @var int
     */
    public $startTime = 0;

    /**
     * Start time calculator
     */
    public function start(){
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $this->startTime = $time;
    }

    /**
     * @return float
     */
    public function calculate(){
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];

        return round(($time - $this->startTime), 4);
    }
}