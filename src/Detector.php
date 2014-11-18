<?php

namespace Sokil\FraudDetector;

class Detector
{
    const STATE_UNCHECKED   = 'unckecked';
    const STATE_PASSED      = 'checkPassed';
    const STATE_FAILED      = 'checkFailed';
    
    private $processorNamespaces = array(
        '\Sokil\FraudDetector\Processor',
    );
    
    private $state = self::STATE_UNCHECKED;
    
    private $handlers = array();
    
    private $processors = array();
    
    /**
     *
     * @var \SplPriorityQueue
     */
    private $processorPriority;
    
    /**
     *
     * @var mixed key to identify unique user
     */
    private $key;
    
    public function __construct()
    {
        $this->processorPriority = new \SplPriorityQueue;
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
    
    public function getKey()
    {
        if(!$this->key) {
            $this->key = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        }
        
        return $this->key;
    }
    
    /**
     * Check if request is not fraud
     */
    public function check()
    {
        // check all conditions
        /* @var $processor \Sokil\FraudDetector\AbstractProcessor */
        foreach($this->processors as $processor) {
            if($processor->process()) {
                $this->setState(self::STATE_PASSED);
            } else {
                $this->setState(self::STATE_FAILED);
            }
        }

        $this->callDelayedHandlers();
    }
     
    /**
     * Factory method to create new check condition
     * 
     * @param string $name name of check condition
     * @return \Sokil\FraudDetector\AbstractProcessor
     * @throws \Exception
     */
    private function createProcessor($name)
    {
        $className = ucfirst($name) . 'Processor';
        
        foreach($this->processorNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                return new $fullyQualifiedClassName($this);
            }
        }
        
        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }
    
    /**
     * Add processor identified by its name.
     * If processor already added, it will be replaced by new instance.
     * 
     * @param string $name name of processor
     * @param callable $callable configurator callable
     * @return \Sokil\FraudDetector\Detector
     */
    public function addProcessor($name, $callable = null, $priority = 0)
    {        
        // create condition
        $condition = $this->createProcessor($name);
        
        // configure condition
        if($callable && is_callable($callable)) {
            call_user_func($callable, $condition);
        }
        
        // add to list
        $this->processors[$name] = $condition;
        
        $this->processorPriority->insert($name, $priority)
        
        return $this;
    }
    
    /**
     * 
     * @param type $name
     * @return \Sokil\FraudDetector\AbstractProcessor
     * @throws \Exception
     */
    public function getProcessor($name)
    {
        if(!$this->isProcessorConfigured($name)) {
            throw new \Exception('Processor ' . $name . ' not found');
        }
        
        return $this->processors[$name];
    }
    
    public function isProcessorConfigured($name)
    {
        return isset($this->processors[$name]);
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