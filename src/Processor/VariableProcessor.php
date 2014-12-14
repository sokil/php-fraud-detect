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

    private $variableType;

    public function setName($name, $variableType = 'GLOBALS')
    {
        $this->name = $name;

        $allowedVariableTypes = array(
            'GLOBALS',
            '_GET',
            '_POST',
            '_SERVER',
            '_REQUEST',
            '_SESSION',
            '_COOKIE',
            '_FILES',
            '_ENV',
        );

        if(!in_array($variableType, $allowedVariableTypes)) {
            throw new \Exception('Variable type must be one of ' . implode(', ', $allowedVariableTypes));
        }

        $this->variableType = $variableType;

        return $this;
    }

    protected function getValue()
    {
        switch($this->variableType) {
            case 'GLOBALS'  : return $GLOBALS[$this->name];
            case '_GET'     : return $_GET[$this->name];
            case '_POST'    : return $_POST[$this->name];
            case '_SERVER'  : return $_SERVER[$this->name];
            case '_REQUEST' : return $_REQUEST[$this->name];
            case '_SESSION' : return $_SESSION[$this->name];
            case '_COOKIE'  : return $_COOKIE[$this->name];
            case '_FILES'   : return $_FILES[$this->name];
            case '_ENV'     : return $_ENV[$this->name];
        }
    }

    public function isPassed()
    {
        foreach($this->conditionList as $condition => $conditionValue) {
            switch($condition) {

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