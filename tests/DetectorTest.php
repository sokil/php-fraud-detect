<?php

namespace Sokil\FraudDetector;

class DetectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCheck()
    {
        $detector = new Detector();
        $that = $this;
        
        $status = new \stdClass();
        $status->ok = false;
        
        $GLOBALS['globalVariable'] = 42;
        
        $detector
            ->addCondition('variable', function($condition) {
                $condition
                    ->setName('globalVariable')
                    ->greater(40)
                    ->lower(44);
            })
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