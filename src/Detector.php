<?php

namespace Sokil\FraudDetector;

class Detector
{
    private $passed;
    
    private $handlers = array(
        'checkPassed' => array(),
        'checkFailed' => array(),
    );
    
    public function onCheckPassed($callable)
    {
        return $this->addHandler('checkPassed', $callable);
    }
    
    public function onCheckFailed($callable)
    {
        return $this->addHandler('checkFailed', $callable);
    }
    
    private function addHandler($name, $callable)
    {
        $this->handlers[$name][] = $callable;
        return $this;
    }
    
    private function callHandlers($name)
    {
        foreach($this->handlers[$name] as $callable) {
            call_user_func($callable);
        }
        
        return $this;
    }
    
    public function check()
    {
        // Check already done. Just call handlers
        if(null !== $this->passed) {
            if($this->passed) {
                // call passed handlers
                $this->callHandlers('checkPassed');
            } else {
                // call failed handlers
                $this->callHandlers('checkFailed');
            }
        }
        
        // check
        
        // trigger handlers
    }
}