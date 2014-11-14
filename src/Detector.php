<?php

namespace Sokil\FraudDetector;

class Detector
{
    const STATE_UNCHECKED   = 'unckecked';
    const STATE_PASSED      = 'checkPassed';
    const STATE_FAILED      = 'checkFailed';
    
    private $conditionNamespaces = array(
        '\Sokil\FraudDetector\Condition',
    );
    
    private $collectorNamespaces = array(
        '\Sokil\FraudDetector\Collector',
    );
    
    private $state = self::STATE_UNCHECKED;
    
    private $handlers = array();
    
    private $conditions = array();
    
    private $collector;
    
    /**
     *
     * @var mixed key to identify unique user
     */
    private $key;
    
    public function __construct()
    {
        // default key is ip of user
        $this->key = $_SERVER['REMOTE_ADDR'];
        
        // define collectiong of successfully passed requests 
        // to gather stat for ban list
        $that = $this;
        $this->onCheckPassed(function() use($that) {
            $this->collect($that->key);
        });
    }
    
    /**
     * Key that uniquely identify user
     * @param type $key
     * @return \Sokil\FraudDetector\Detector
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }
    
    public function setCollector($type, $storage)
    {
        $this->collector = $this
            ->createCollector($type)
            ->setStorage($storage);
    }
    
    /**
     * Factory method to create new collector
     * 
     * @param string $type Type of storage
     * @return \Sokil\FraudDetector\AbstractCollector
     * @throws \Exception
     */
    private function createCollector($type)
    {
        $className = ucfirst($type);
        
        foreach($this->collectorNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                return new $fullyQualifiedClassName();
            }
        }
        
        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }
    
    private function collect($key)
    {
        if(!$this->collector) {
            throw new \Exception('Collector not configured');
        }
        
        $this->collector->collect($key);
        
        return $this;
    }
    
    /**
     * Check if request is not fraud
     */
    public function check()
    {
        foreach($this->conditions as $condition) {
            $state = $condition->isPassed()
                ? self::STATE_PASSED
                : self::STATE_FAILED;

            $this->setState($state);
        }
        
        $this->callDelayedHandlers();
    }
     
    /**
     * Factory method to create new check condition
     * 
     * @param string $name name of check condition
     * @return \Sokil\FraudDetector\AbstractCondition
     * @throws \Exception
     */
    private function createCondition($name)
    {
        $className = ucfirst($name);
        
        foreach($this->conditionNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                return new $fullyQualifiedClassName();
            }
        }
        
        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }
    
    public function addCondition($name, $callable = null)
    {        
        // create condition
        $condition = $this->createCondition($name);
        
        // configure condition
        if($callable && is_callable($callable)) {
            call_user_func($callable, $condition);
        }
        
        // add to list
        $this->conditions[] = $condition;
        
        return $this;
    }
    
    public function onCheckPassed($callable)
    {
        $this->on(self::STATE_PASSED, $callable);
        
        return $this;
    }
    
    public function onCheckFailed($callable)
    {
        $this->on(self::STATE_FAILED, $callable);
        
        return $this;
    }
    
    public function isUnchecked()
    {
        return $this->hasState(self::STATE_UNCHECKED);
    }
    
    public function isPassed()
    {
        return $this->hasState(self::STATE_PASSED);
    }
    
    public function isFailed()
    {
        return $this->hasState(self::STATE_FAILED);
    }
    
    private function on($stateName, $callable)
    {
        if($this->hasState(self::STATE_UNCHECKED)) {
            $this->delayHandler($stateName, $callable);
        } elseif($this->hasState($stateName)) {
            $this->callHandler($callable);
        }
        
        return $this;
    }
    
    private function callHandler($callable)
    {
        call_user_func($callable);
        return $this;
    }
    
    private function delayHandler($stateName, $callable)
    {
        $this->handlers[$stateName][] = $callable;
        return $this;
    }
    
    private function callDelayedHandlers()
    {
        if(empty($this->handlers[$this->state])) {
            return $this;
        }
        
        foreach($this->handlers[$this->state] as $callable) {
            $this->callHandler($callable);
        }
        
        return $this;
    }
    
    private function hasState($state)
    {
        return $this->state === $state;
    }
    
    private function setState($state)
    {
        $this->state = $state;
        return $this;
    }
}