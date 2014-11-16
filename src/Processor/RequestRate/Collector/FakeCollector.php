<?php

namespace Sokil\FraudDetector\Processor\RequestRate\Collector;

use \Sokil\FraudDetector\Processor\RequestRate\AbstractCollector;

class FakeCollector extends AbstractCollector
{
    private static $keyList = array();
    
    public function isRateLimitExceed()
    {
        // is key not exists
        if(!isset(self::$keyList[$this->key])) {
            return false;
        }
        
        // is in time slot
        if(self::$keyList[$this->key]['time'] + $this->timeInterval < time()) {
            return false;
        }
        
        // is requests limit reached
        return self::$keyList[$this->key]['requestNum'] >= $this->requestNum;
    }
    
    public function collect()
    {
        if(isset(self::$keyList[$this->key])) {
            self::$keyList[$this->key]['requestNum']++;
        } else {
            self::$keyList[$this->key] = array('time' => time(), 'requestNum' => 1);
        }
    }
}