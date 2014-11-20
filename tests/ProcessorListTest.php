<?php

namespace Sokil\FraudDetector;

class ProcessorListTest extends \PHPUnit_Framework_TestCase
{
    public function testDeclareProcessor()
    {
        $list = new ProcessorList(new Detector);
        $list->registerNamespace('\Sokil\FraudDetector');
        
        $list->declareProcessor('some10', function() {}, 10);
        
        $list->declareProcessor('some100', function() {}, 100);
        
        $list->declareProcessor('some1000', function() {}, 1000);
        
        $this->assertEquals('some1000', $list->key());
        $list->next();
        
        $this->assertEquals('some100', $list->key());
        $list->next();
        
        $this->assertEquals('some10', $list->key());
    }
    
    public function testIsProcessorConfigured()
    {
        $list = new ProcessorList(new Detector);
        
        $list->declareProcessor('blackList');
        
        $this->assertTrue($list->isProcessorConfigured('blackList'));
        
        $this->assertFalse($list->isProcessorConfigured('SOME_UNEXISTED_PROCESSOR'));
    }
}

class Some10Processor extends AbstractProcessor {
    public function isPassed() { return true; }
}

class Some100Processor extends Some10Processor {}

class Some1000Processor extends Some100Processor {}