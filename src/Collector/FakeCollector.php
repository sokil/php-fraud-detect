<?php

namespace Sokil\FraudDetector\Collector;

class FakeCollector extends \Sokil\FraudDetector\AbstractCollector
{
    protected function init()
    {
        $this->storage = array();
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
        return $this->storage[$this->key]['time'] + $this->timeInterval > time();
    }
    
    private function isRequestLimitExceed()
    {        
        return $this->storage[$this->key]['requestNum'] >= $this->requestNum;
    }
    
    public function collect()
    {
        if(!$this->keyExists() || !$this->isInTimeSlot()) {
            $this->initKey();
            return true;
        }
        
        if($this->isRequestLimitExceed()) {
            return false;
        }
        
        $this->incrementKeyRequestNum();
        return true;
        
    }
}