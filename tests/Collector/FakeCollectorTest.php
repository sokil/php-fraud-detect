<?php

namespace Sokil\FraudDetector\Collector;

class DetectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollect()
    {
        $collector = new FakeCollector('key', 10, 1);
        
        for($i = 0; $i < 10; $i++) {
            $this->assertTrue($collector->collect());
        }
        
        $this->assertFalse($collector->collect());
    }
}