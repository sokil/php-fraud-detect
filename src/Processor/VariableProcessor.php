<?php

namespace Sokil\FraudDetector\Processor;

class VariableProcessor extends \Sokil\FraudDetector\AbstractProcessor
{
    const CONDITION_EXISTS      = 'exists';
    const CONDITION_EQUALS      = '==';
    const CONDITION_NOT_EQUALS  = '!=';
    const CONDITION_GREATER     = '>';
    const CONDITION_LOWER       = '<';
    const CONDITION_INSTANCEOF  = 'instanceof';
    
    private $name;
    
    private $conditionList;
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    protected function getValue()
    {        
        return $GLOBALS[$this->name];
    }
    
    protected function isValueExists()
    {
        return array_key_exists($this->name, $GLOBALS);
    }
    
    public function isPassed() 
    {        
        foreach($this->conditionList as $condition => $conditionValue) {
            switch($condition) {
                
                case self::CONDITION_EXISTS:
                    $passed = $this->isValueExists();
                    break;
                    
                case self::CONDITION_EQUALS:
                    $passed = ($this->getValue() === $conditionValue);
                    break;

                case self::CONDITION_NOT_EQUALS:
                    $passed = ($this->getValue() !== $conditionValue);
                    break;

                case self::CONDITION_GREATER:
                    $passed = ($this->getValue() > $conditionValue);
                    break;

                case self::CONDITION_LOWER:
                    $passed = ($this->getValue() < $conditionValue);
                    break;

                case self::CONDITION_INSTANCEOF:
                    $passed = ($this->getValue() instanceof $conditionValue);
                    break;

                default:
                    throw new \Exception('Wrong condition ' . $condition . ' specified');
            }
            
            if(!$passed) {
                return false;
            }
        }
        
        return true;
    }
    
    
    public function equals($value)
    {
        $this->conditionList[self::CONDITION_EQUALS] = $value;
        return $this;
    }
    
    public function notEquals($value)
    {
        $this->conditionList[self::CONDITION_NOT_EQUALS] = $value;
        return $this;
    }
    
    public function greater($value)
    {
        $this->conditionList[self::CONDITION_GREATER] = $value;
        return $this;
    }
    
    public function lower($value)
    {
        $this->conditionList[self::CONDITION_LOWER] = $value;
        return $this;
    }
    
    public function isInstanceOf($value)
    {
        $this->conditionList[self::CONDITION_INSTANCEOF] = $value;
        return $this;
    }
}