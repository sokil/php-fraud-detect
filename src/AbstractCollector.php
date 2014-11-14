<?php

namespace Sokil\FraudDetector;

abstract class AbstractCollector
{
    private $storage;
    
    private $allowedRequestRate = 1;
    
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
        $this->allowedRequestRate = $requestNum / $timeIntervalLengthInSeconds;
        return $this;
    }
    
    protected function getRate()
    {
        return $this->allowedRequestRate;
    }
    
    public function collect($key)
    {
        $this->store($key, time());
    }
    
    abstract protected function store($key, $time);
    
    abstract public function isLimitExceed($key);
}