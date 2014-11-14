<?php

namespace Sokil\FraudDetector;

abstract class AbstractCollector
{
    protected $storage;
    
    public function setStorage($storage)
    {
        $this->storage = $storage;
        return $this;
    }
    
    abstract public function collect($key);
}