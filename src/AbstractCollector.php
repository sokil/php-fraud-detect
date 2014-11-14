<?php

namespace Sokil\FraudDetector;

abstract class AbstractCollector
{
    protected $storage;
    
    protected $key;
    
    protected $requestNum;
    
    protected $timeInterval;
    
    public function __construct($key, $requestNum, $timeInterval)
    {
        $this->key = $key;
        
        $this->requestNum = $requestNum;
        
        $this->timeInterval = $timeInterval;
        
        $this->init();
    }
    
    protected function init() {}
    
    public function setStorage($storage)
    {
        $this->storage = $storage;
        return $this;
    }
    
    abstract public function collect();
}