<?php

namespace Sokil\FraudDetector\Condition;

class Rate extends \Sokil\FraudDetector\AbstractCondition
{
    private $requestNum = 1;
    
    private $timeIntervalLengthInSeconds = 1;
    
    public function setRate($requestNum, $timeIntervalLengthInSeconds)
    {
        $this->requestNum = $requestNum;
        $this->timeIntervalLengthInSeconds = $timeIntervalLengthInSeconds;
        return $this;
    }
    
    abstract public function isPassed()
    {
        
    }
    
}