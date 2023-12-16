<?php 
namespace Funclib;

class PerformanceMeasure

{
    protected static PerformanceMeasure $instance;
    
    protected array $items;
    private float $timestamp;
    private int $displayLevel = 3;

    public static function getInstance() : PerformanceMeasure {
        if(empty(PerformanceMeasure::$instance)) {
            PerformanceMeasure::$instance = new PerformanceMeasure();
        }
        return PerformanceMeasure::$instance;
    }
    
    private function __construct()
    {
           $this->items = array();
    }
    
    public function setDisplayLevel(int $level) : void
    {
        $this->displayLevel = $level;
    }
    
    public function StartMeasurement() : void
    {
        $this->timestamp = microtime(true);
    }
    
    public function addMeasurementCheckpoint(string $keyName, int $level = 3)
    {
        $this->items[$keyName]['time'] = microtime(true);
        $this->items[$keyName]['key'] = $keyName;
        $this->items[$keyName]['stopped'] = false;
        $this->items[$keyName]['level'] = $level;
    }
    
    public function stopMeasurementCheckpoint(string $keyName)
    {
        if(array_key_exists($keyName, $this->items))
        {
            $this->items[$keyName]['measure'] = microtime(true) - $this->items[$keyName]['time'];
            $this->items[$keyName]['measureString'] = round($this->items[$keyName]['measure']*1000,2);
            $this->items[$keyName]['stopped'] = true;
        }
    }
    
    public function getMeasurements() : array
    {
        foreach($this->items as $item) {
            if(!$item['stopped'])
            {
                $this->stopMeasurementCheckpoint($item['key']);
            }
        }
        return $this->items;
    }
    
    public function getMeasurementsOutputString() 
    {
        $result = array();
        $timeResult = $this->getMeasurements();
        foreach($timeResult as $time)
        {
            if($time['level'] >= $this->displayLevel )
            $result[] = array('' => $time['measure']);
        }
        return $result;
    }
    
    public function getTotalTime() : float
    {
        return microtime(true) - $this->timestamp;
    }
    
    public function getMeasurementString() : string
    {
        $result = "";
        foreach($this->items as $item) 
        {
            $result .= round($item['measure'],2)." ms <br>";
        }
        $result .= "Total: ".(round($this->getTotalTime(),2)*1000)." ms";
        return $result;
    }
}

?>