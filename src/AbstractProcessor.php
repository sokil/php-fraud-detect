<?php

namespace Sokil\FraudDetector;

abstract class AbstractProcessor
{
    /**
     *
     * @var \Sokil\FraudDetector\Detector
     */
    protected $detector;
    
    public function __construct(Detector $detector)
    {
        $this->detector = $detector;
    }
    
    public function process()
    {
        if($this->isPassed()) {
            $this->afterCheckPassed();
            return true;
        } else {
            $this->afterCheckFailed();
            return false;
        }
    }
    
    abstract protected function isPassed();
    
    protected function afterCheckPassed() {}
    
    protected function afterCheckFailed() {}
}