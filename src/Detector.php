<?php

namespace Sokil\FraudDetector;

class Detector
{
    private $passed;
    
    public function onCheckPassed($callable)
    {
        
    }
    
    public function onCheckFailed($callable)
    {
        
    }
    
    public function check()
    {
        if(null !== $this->passed) {
            if($this->passed) {
                // call passed handlers
            } else {
                // call failed handlers
            }
        }
        
        // check
        
        // trigger handlers
    }
}