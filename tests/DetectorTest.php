<?php

namespace Sokil\FraudDetector;

class DetectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCheck_Variable()
    {
        $detector = new Detector();
        
        $status = new \stdClass();
        $status->ok = null;
        
        $GLOBALS['globalVariable'] = 42;
        
        $detector
            ->setKey('someKey')
            ->setRequestRate(5, 1)
            ->setCollector('fake')
            ->setBlackListStorage('fake')
            ->addCondition('variable', function($condition) {
                $condition
                    ->setName('globalVariable')
                    ->greater(40)
                    ->lower(44);
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
    
    public function testCheck_Proxy()
    {
        $detector = new Detector();
        $that = $this;
        
        $status = new \stdClass();
        $status->ok = false;
        
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';
        
        $detector
            ->setKey('someKey')
            ->setCollector('fake')
            ->addCondition('noProxy')
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($that) {
                $that->fail('Check must pass, but fail');
            })
            ->check();
            
        $this->assertTrue($status->ok);
    }
}