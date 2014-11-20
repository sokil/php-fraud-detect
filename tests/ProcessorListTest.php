<?php

namespace Sokil\FraudDetector;

class ProcessorListTest extends \PHPUnit_Framework_TestCase
{
    public function testDeclareProcessor()
    {
        $list = new ProcessorList;
        
        $list->declareProcessor('someProcessor10', function() {}, 10);
        
        $list->declareProcessor('someProcessor100', function() {}, 100);
        
        $list->declareProcessor('someProcessor100', function() {}, 1000);
        
        $this->assertEquals(1000, $list->current());
        $list->next();
        
        $this->assertEquals(100, $list->current());
        $list->next();
        
        $this->assertEquals(10, $list->current());
    }
    
    public function testIsProcessorConfigured()
    {
        $list = new ProcessorList();
        
        $list->declareProcessor('blackList');
        
        $this->assertTrue($list->isProcessorConfigured('blackList'));
        
        $this->assertFalse($list->isProcessorConfigured('SOME_UNEXISTED_PROCESSOR'));
    }
}

class SomeProcessor10 extends AbstractProcessor {
    public function isPassed() { return true; }
}

class SomeProcessor100 extends SomeProcessor10 {}

class SomeProcessor1000 extends SomeProcessor100 {}