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

    protected function isValueExists()
    {
        switch($this->variableType) {
            case 'GLOBALS'  : return array_key_exists($this->name, $GLOBALS);
            case '_GET'     : return array_key_exists($this->name, $_GET);
            case '_POST'    : return array_key_exists($this->name, $_POST);
            case '_SERVER'  : return array_key_exists($this->name, $_SERVER);
            case '_REQUEST' : return array_key_exists($this->name, $_REQUEST);
            case '_SESSION' : return array_key_exists($this->name, $_SESSION);
            case '_COOKIE'  : return array_key_exists($this->name, $_COOKIE);
            case '_FILES'   : return array_key_exists($this->name, $_FILES);
            case '_ENV'     : return array_key_exists($this->name, $_ENV);
        }
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