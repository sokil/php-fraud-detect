<?php

namespace Sokil\FraudDetector\Processor;

class RequestRateProcessor extends \Sokil\FraudDetector\AbstractProcessor
{
    private $requestNumber = 1;
    
    private $timeInterval = 1;
    
    /**
     *
     * @var \Sokil\FraudDetector\Processor\RequestRate\AbstractCollector
     */
    private $collector;
    
    private $banOnRateExceed = false;
    
    protected function isPassed()
    {        
        return !$this->collector->isRateLimitExceed();
    }
    
    protected function afterCheckPassed()
    {
        $this->collector->collect();
        return $this;
    }
    
    protected function afterCheckFailed()
    {
        if($this->banOnRateExceed) {
            $this->detector
                ->getProcessor('blackList')
                ->store($this->detector->getKey());
        }
        
        return $this;
    }
    
    public function banOnRateExceed()
    {
        $this->banOnRateExceed = true;
        return $this;
    }
    
    public function setRequestRate($requestNumber, $timeInterval)
    {
        $this->requestNumber = $requestNumber;
        $this->timeInterval = $timeInterval;
        return $this;
    }
    
    public function setCollector($type, $configuratorCallable = null)
    {        
        $className = '\Sokil\FraudDetector\Processor\RequestRate\Collector\\' . ucfirst($type) . 'Collector';
        
        if(!class_exists($className)) {
            throw new \Exception('Collector ' . $type . ' not found');
        }

        $this->collector = new $className(
            $this->detector->getKey(),
            $this->requestNumber, 
            $this->timeInterval
        );

        // configure
        if(is_callable($configuratorCallable)) {
            call_user_func($configuratorCallable, $this->collector);
        }
        
        return $this;
        
    }
}