<?php

namespace Sokil\FraudDetector;

class Detector
{
    const STATE_UNCHECKED   = 'unckecked';
    const STATE_SKIPPED     = 'skipped';
    const STATE_PASSED      = 'checkPassed';
    const STATE_FAILED      = 'checkFailed';
    
    private $checkConditionNamespaces = array(
        '\Sokil\FraudDetector\CheckCondition',
    );
    
    private $banConditionNamespaces = array(
        '\Sokil\FraudDetector\BanCondition',
    );
    
    private $state = self::STATE_UNCHECKED;
    
    private $handlers = array();
    
    private $checkConditions = array();
    
    private $banConditions = array();
    
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
     * @return \Sokil\FraudDetector\fullyQualifiedClassName
     * @throws \Exception
     */
    private function createCheckCondition($name)
    {
        $className = ucfirst($name);
        
        foreach($this->checkConditionNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                return new $fullyQualifiedClassName();
            }
        }
        
        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }
    
    /**
     * Factory method to create new ban condition
     * 
     * @param string $name name of check condition
     * @return \Sokil\FraudDetector\fullyQualifiedClassName
     * @throws \Exception
     */
    private function createBanCondition($name)
    {
        $className = ucfirst($name);
        
        foreach($this->banConditionNamespaces as $namespace) {
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
        // is check required
        foreach($this->checkConditions as $condition) {
            if(!$condition->passed()) {
                $this->setState(self::STATE_SKIPPED);
                return;
            }
        }
        
        
        // is ban required
        if($this->banConditions) {
            foreach($this->banConditions as $condition) {
                $state = $condition->passed()
                    ? self::STATE_PASSED
                    : self::STATE_FAILED;

                $this->setState($state);
            }
        } else {
            $this->setState(self::STATE_PASSED);
        }
        
        $this->callDelayedHandlers();
    }
    
    public function addCheckCondition($name, $callable)
    {
        if(!is_callable($callable)) {
            throw new \Exception('Wrong callable');
        }
        
        // create condition
        $condition = $this->createCheckCondition($name);
        
        // configure condition
        call_user_func($callable, $condition);
        
        // add to list
        $this->checkConditions[] = $condition;
        
        return $this;
    }
    
    public function addBanCondition($name, $callable)
    {
        if(!is_callable($callable)) {
            throw new \Exception('Wrong callable');
        }
        
        // create condition
        $condition = $this->createBanCondition($name);
        
        // configure condition
        call_user_func($callable, $condition);
        
        // add to list
        $this->banConditions[] = $condition;
        
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
    
    public function onCheckSkipped($callable)
    {
        $this->on(self::STATE_SKIPPED, $callable);
        
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
    
    public function isSkipped()
    {
        return $this->hasState(self::STATE_SKIPPED);
    }
    
    private function configure(array $config)
    {
        // check condifion
        if(!empty($config['condition']['check']) && is_array($config['condition']['check'])) {
            foreach($config['condition']['check'] as $conditionDefinition) {
                $conditionClassName = $conditionDefinition[0];
                $this->addCheckCondition(new $conditionClassName($conditionDefinition));
            }
        }
        
        // ban condifion
        if(!empty($config['condition']['ban']) && is_array($config['condition']['ban'])) {
            foreach($config['condition']['check'] as $conditionDefinition) {
                $conditionClassName = $conditionDefinition[0];
                $this->addBanCondition(new $conditionClassName($conditionDefinition));
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