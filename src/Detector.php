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
    
    private $state = self::STATE_UNCHECKED;
    
    private $handlers = array();
    
    private $conditions = array();
    
    public function __construct(array $config = null)
    {
        if($config) {
            $this->configure($config);
        }
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
    
    /**
     * Check if request is not fraud
     */
    public function check()
    {          
        foreach($this->conditions as $condition) {
            $state = $condition->passed()
                ? self::STATE_PASSED
                : self::STATE_FAILED;

            $this->setState($state);
        }
        
        $this->callDelayedHandlers();
    }
    
    public function addCondition($name, $callable)
    {
        if(!is_callable($callable)) {
            throw new \Exception('Wrong callable');
        }
        
        // create condition
        $condition = $this->createCondition($name);
        
        // configure condition
        call_user_func($callable, $condition);
        
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
    
    private function configure(array $config)
    {
        if(!empty($config['condition']) && is_array($config['condition'])) {
            foreach($config['condition'] as $conditionDefinition) {

            }
        }
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