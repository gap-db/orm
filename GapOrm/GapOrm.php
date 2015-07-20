<?php
namespace GapOrm;

use GapOrm\Exceptions\DriverNotFound;
use GapOrm\Profiler\Profiler;
use Safan\Safan;

class GapOrm
{
    /**
     * Available drivers
     *
     * @var array
     */
    private $drivers = array(
        'pdo' => 'GapOrm\Drivers\PdoDriver'
    );

    /**
     * @var object
     */
    private static $driverInstance;

    /**
     * Connect to driver
     *
     * @param $dbConfig
     * @throws Exceptions\DriverNotFound
     */
    public function init($dbConfig){
        $profiler = new Profiler();

        if(isset($this->drivers[$dbConfig['driver']])){
            $driverClass = $this->drivers[$dbConfig['driver']];
            self::$driverInstance = $driverClass::getInstance();

            $profiler->getTimer()->start();
            self::$driverInstance->setup($dbConfig);

            $profilerOptions = [
                'type' => 'connection',
                'time' => $profiler->getTimer()->calculate()
            ];

            $profiler->setOptions('dbConnection', $profilerOptions);
            Safan::handler()->getObjectManager()->setObject('gapOrmProfiler', $profiler);
        }
        else
            throw new DriverNotFound($dbConfig['driver']);
    }

    /**
     * Return selected driver instance
     *
     * @return mixed
     */
    public static function getDriver(){
        return self::$driverInstance;
    }
}