<?php

namespace Sokil\FraudDetector\Processor\RequestRate\Collector;

class FakeCollector extends AbstractCollector
{
    private $keyList = array();

    public function isRateLimitExceed()
    {
        $timeNow = microtime(true);

        // is key not exists
        if(!isset($this->keyList[$this->key])) {
            return false;
        }

        // is in time slot
        if($this->keyList[$this->key]['expired'] < $timeNow) {
            return false;
        }

        // is requests limit reached
        return $this->keyList[$this->key]['requestNum'] >= $this->requestNum;
    }

    public function collect()
    {
        $timeNow = microtime(true);

        if(isset($this->keyList[$this->key]) && $this->keyList[$this->key]['expired'] > $timeNow) {
            $this->keyList[$this->key]['requestNum']++;
        } else {
            $this->keyList[$this->key] = array('expired' => $timeNow + $this->timeInterval, 'requestNum' => 1);
        }
    }
}