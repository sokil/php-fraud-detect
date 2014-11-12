<?php

namespace Sokil\FraudDetector;

class Detector
{
    const STATE_UNCHECKED   = 'unckecked';
    const STATE_SKIPPED     = 'skipped';
    const STATE_PASSED      = 'checkPassed';
    const STATE_FAILED      = 'checkFailed';
    
    private $state;
    
    private $handlers = array();
    
    private $checkConditions = array();
    
    private $banConditions = array();
    
    public function check()
    {        
        $this->callDelayedHandlers();
    }
    
    public function addCheckCondition(AbstractCheckCondition $condition)
    {
        $this->checkConditions[] = $condition;
        return $this;
    }
    
    public function addBanCondition(AbstractBanCondition $condition)
    {
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
    
    private function on($stateName, $callable)
    {
        if($this->hasState(self::STATE_UNCHECKED)) {
            $this->delayHandler(self::STATE_FAILED, $callable);
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
    
    private function delayHandler($name, $callable)
    {
        $this->handlers[$name][] = $callable;
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
    
    private function hasState($name)
    {
        return $this->state === $name;
    }
}