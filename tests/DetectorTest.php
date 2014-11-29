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
            ->declareProcessor('requestRate', function(\Sokil\FraudDetector\Processor\RequestRateProcessor $processor) {
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


        $detector
            ->setKey('someKey')
            ->declareProcessor('variable', function(\Sokil\FraudDetector\Processor\VariableProcessor $processor) {
                /* @var $processor \Sokil\FraudDetector\Processor\VariableProcessor */
                $processor->setName('globalVariable')->equals(42);
            })
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($status) {
                $status->ok = false;
            });

        $GLOBALS['globalVariable'] = 42;
        $detector->check();
        $this->assertTrue($status->ok);

        $GLOBALS['globalVariable'] = 43;
        $detector->check();
        $this->assertFalse($status->ok);
    }

    public function testCheck_NoProxy()
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;

        $GLOBALS['globalVariable'] = 42;

        $detector
            ->setKey('someKey')
            ->declareProcessor('noProxy')
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($status) {
                $status->ok = false;
            });

        $detector->check();
        $this->assertTrue($status->ok);

        $_SERVER['VIA'] = '10.0.0.1';
        $detector->check();
        $this->assertFalse($status->ok);

    }

    public function testCheck_AddToBlackListOnRateExceed()
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;

        $detector
            ->setKey('someKey')
            ->declareProcessor('requestRate', function(\Sokil\FraudDetector\Processor\RequestRateProcessor $processor) {
                /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
                $processor
                    ->setRequestRate(5, 1)
                    ->banOnRateExceed()
                    ->setCollector('fake');
            })
            ->declareProcessor('blackList', function($processor) {
                /* @var $processor \Sokil\FraudDetector\Processor\BlackListProcessor */
                $processor->setStorage('fake');
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
            $this->assertFalse($detector->getProcessor('blackList')->isBanned());
        }

        $detector->check();
        $this->assertFalse($status->ok);
        $this->assertTrue($detector->getProcessor('blackList')->isBanned());
    }

    public function testCheck_BlackList()
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;

        $detector
            ->setKey('someKey')
            ->declareProcessor('blackList', function($processor) {
                /* @var $processor \Sokil\FraudDetector\Processor\BlackListProcessor */
                $processor->setStorage('fake');
            })
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($status) {
                $status->ok = false;
            });

        $detector->check();
        $this->assertTrue($status->ok);

    }

    public function testGetProcessor()
    {
        $detector = new Detector();
        $detector->declareProcessor('blackList');

        $reflectionClass = new \ReflectionClass($detector);
        $method = $reflectionClass->getMethod('getProcessor');
        $method->setAccessible(true);

        $this->assertInstanceOf(
            '\Sokil\FraudDetector\Processor\BlackListProcessor',
            $method->invoke($detector, 'blackList')
        );
    }

    public function testIsProcessorDeclared()
    {
        $detector = new Detector;

        $detector->declareProcessor('blackList');

        $this->assertTrue($detector->isProcessorDeclared('blackList'));

        $this->assertFalse($detector->isProcessorDeclared('SOME_UNEXISTED_PROCESSOR'));
    }
}