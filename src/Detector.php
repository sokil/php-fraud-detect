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
    
    private $collectorType;
    
    private $collectorConfiguratorCallable;
    
    private $collector;
    
    /**
     *
     * @var mixed key to identify unique user
     */
    private $key;
    
    private $requestNumber = 1;
    
    private $timeInterval = 1;
    
    public function __construct()
    {
        // default key is ip of user
        $this->key = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
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
    
    public function setRequestRate($requestNumber, $timeInterval)
    {
        $this->requestNumber = $requestNumber;
        $this->timeInterval = $timeInterval;
        return $this;
    }
    
    public function setCollector($type, $callable = null)
    {
        $this->collectorType = $type;
        
        if($callable && is_callable($callable)) {
            $this->collectorConfiguratorCallable = $callable;
        }
        
        $this->collector = null;
        
        return $this;
    }
    
    /**
     * Factory method to create new collector
     * 
     * @param string $type Type of storage
     * @return \Sokil\FraudDetector\AbstractCollector
     * @throws \Exception
     */
    private function getCollector()
    {
        if($this->collector) {
            return $this->collector;
        }
        
        $className = ucfirst($this->collectorType) . 'Collector';
        
        foreach($this->collectorNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                $this->collector = new $fullyQualifiedClassName($this->key, $this->requestNumber, $this->timeInterval);
                
                // configure
                if($this->collectorConfiguratorCallable) {
                    call_user_func($this->collectorConfiguratorCallable, $this->collector);
                }
                
                return $this->collector;
            }
        }
        
        throw new \Exception('Collector ' . $this->collectorType . ' not found');
    }
    
    /**
     * Check if request is not fraud
     */
    public function check()
    {
        // check all conditions
        foreach($this->conditions as $condition) {
            $state = $condition->isPassed()
                ? self::STATE_PASSED
                : self::STATE_FAILED;

            $this->setState($state);
        }
        
        // collect stat
        if($this->isPassed()) {
            if(!$this->getCollector()->collect()) {
                // ban key
            }
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
    
    private function hasState($state)
    {
        return $this->state === $state;
    }
    
    private function setState($state)
    {
        $this->state = $state;
        return $this;
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
}