<?php

namespace Sokil\FraudDetector\Collector;

class FakeCollector extends \Sokil\FraudDetector\AbstractCollector
{
    protected $storage = array();
    
    private $key;
    
    private $requestNum;
    
    private $timeInterval;
    
    public function __construct($key, $requestNum, $timeInterval)
    {
        $this->key = $key;
        
        $this->requestNum = $requestNum;
        
        $this->timeInterval = $timeInterval;
    }
    
    private function keyExists()
    {
        return isset($this->storage[$this->key]);
    }
    
    private function initKey()
    {
        $this->storage[$this->key] = array('time' => time(), 'requestNum' => 1);
        return $this;
    }
    
    private function incrementKeyRequestNum()
    {
        $this->storage[$this->key]['requestNum']++;
        return $this;
    }
    
    private function isInTimeSlot()
    {
        return $this->storage[$this->key]['time'] + $this->timeInterval < time();
    }
    
    private function isRequestLimitExceed()
    {        
        return $this->storage[$this->key]['requestNum'] < $this->requestNum;
    }
    
    public function collect()
    {
        if(!$this->keyExists() || !$this->isInTimeSlot()) {
            $this->initKey();
            return;
        }
        
        if($this->isRequestLimitExceed()) {
            throw new RequestLimitExceedException;
        }
        
        $this->incrementKeyRequestNum();
        return;
        
    }
}