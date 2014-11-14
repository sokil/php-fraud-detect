<?php

namespace Sokil\FraudDetector;

abstract class AbstractCondition
{
    protected $detector;
    
    public function __construct(Detector $detector)
    {
        $this->detector = $detector;
    }
    
    abstract public function isPassed();
}