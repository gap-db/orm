<?php
/**
 * This file is part of the Safan package.
 *
 * (c) Harut Grigoryan <ceo@safanlab.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace GapOrm\Profiler;

use GapOrm\Profiler\Tools\QueryManager;
use GapOrm\Profiler\Tools\Timer;

class Profiler extends BaseProfiler
{
    /**
     * @var
     */
    private $timer;

    /**
     * @var
     */
    private $queryManager;

    /**
     * Set instances
     */
    public function __construct(){
        $this->timer        = new Timer();
        $this->queryManager = new QueryManager();
    }

    /**
     * @return Timer
     */
    public function getTimer(){
        return $this->timer;
    }

    /**
     * @return QueryManager
     */
    public function getQueryManager(){
        return $this->queryManager;
    }
}