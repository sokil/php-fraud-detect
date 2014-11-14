<?php

namespace Sokil\FraudDetector\Collector;

class FakeCollector extends \Sokil\FraudDetector\AbstractCollector
{
    private $storage = array();
    
    private function resetKeyCounter($key)
    {
        $this->storage[$key] = array('time' => time(), 'counter' => 1);
        return $this;
    }
    
    private function incrementKeyCounter($key)
    {
        $this->storage[$key]['counter']++;
        return $this;
    }
    
    private function isInTimeSlot($key)
    {
        return $this->storage[$key]['time'] < time() - $this->getTimeIntervalLengthInSeconds();
    }
    
    private function isRequestNumExceed($key)
    {
        return $this->storage[$key]['counter'] > $this->getRequestNum();
    }
    
    public function collect($key)
    {
        if(empty($this->storage[$key])) {
            $this->resetKeyCounter($key);
        } else {
            if($this->isInTimeSlot($key)) {
                if($this->isRequestNumExceed($key)) {
                    $this->banKey($key);
                } else {
                    $this->incrementKeyCounter($key);
                }
            } else {
                $this->resetKeyCounter($key);
            }
        }
    }
    
    protected function banKey($key)
    {
        
    }
}