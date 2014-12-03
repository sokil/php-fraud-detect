<?php

namespace Sokil\FraudDetector\Collector;

use \Sokil\FraudDetector\AbstractCollector;

class FakeCollector extends AbstractCollector
{
    private $keyList = array();

    public function isRateLimitExceed()
    {
        // is key not exists
        if(!isset($this->keyList[$this->key])) {
            return false;
        }

        // is in time slot
        if($this->keyList[$this->key]['time'] + $this->timeInterval < time()) {
            return false;
        }

        // is requests limit reached
        return $this->keyList[$this->key]['requestNum'] >= $this->requestNum;
    }

    public function collect()
    {
        if(isset($this->keyList[$this->key])) {
            $this->keyList[$this->key]['requestNum']++;
        } else {
            $this->keyList[$this->key] = array('time' => time(), 'requestNum' => 1);
        }
    }
}