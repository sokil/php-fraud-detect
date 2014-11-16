<?php

namespace Sokil\FraudDetector;

class DetectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCheck_RequestRate()
    {
        $detector = new Detector();
        
        $status = new \stdClass();
        $status->ok = null;
        
        $GLOBALS['globalVariable'] = 42;
        
        $detector
            ->setKey('someKey')
            ->addProcessor('requestRate', function(\Sokil\FraudDetector\Processor\RequestRateProcessor $processor) {
                /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
                $processor
                    ->setRequestRate(5, 1)
                    ->setCollector('fake');
            })
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($status) {
                $status->ok = false;
            });
            
        for($i = 0; $i < 5; $i++) {
            $detector->check();
            $this->assertTrue($status->ok);
        }
        
        $detector->check();
        $this->assertFalse($status->ok);    
    }
    
    public function testCheck_Variable()
    {
        $detector = new Detector();
        
        $status = new \stdClass();
        $status->ok = null;
        
        $GLOBALS['globalVariable'] = 42;
        
        $detector
            ->setKey('someKey')
            ->addProcessor('variable', function(\Sokil\FraudDetector\Processor\VariableProcessor $processor) {
                /* @var $processor \Sokil\FraudDetector\Processor\VariableProcessor */
                $processor->setName('globalVariable')->equals(42);
            })
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($status) {
                $status->ok = false;
            })
            ->check();
            
        $this->assertTrue($status->ok);   
    }
}