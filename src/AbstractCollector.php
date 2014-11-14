<?php

namespace Sokil\FraudDetector;

abstract class AbstractCollector
{
    private $storage;
    
    private $requestNum = 1;
    
    private $timeIntervalLengthInSeconds = 1;
    
    public function setStorage($storage)
    {
        $this->storage = $storage;
        return $this;
    }
    
    protected function getStorage()
    {
        return $this->storage;
    }
    
    public function setRate($requestNum, $timeIntervalLengthInSeconds)
    {
        $this->requestNum = $requestNum;
        $this->timeIntervalLengthInSeconds = $timeIntervalLengthInSeconds;
        return $this;
    }
    
    protected function getRequestNum()
    {
        return $this->requestNum;
    }
    
    protected function getTimeIntervalLengthInSeconds()
    {
        return $this->timeIntervalLengthInSeconds;
    }
    
    abstract public function collect($key);
    
    abstract protected function banKey($key);
}