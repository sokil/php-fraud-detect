<?php

namespace Sokil\FraudDetector;

use Sokil\FraudDetector\Processor\RequestRateProcessor;

class DetectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCheck_RequestRate_FakeCollector()
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;

        $detector
            ->setKey('someKey')
            ->declareProcessor('requestRate', function(RequestRateProcessor $processor, Detector $detector) {
                /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
                $processor->setCollector($detector->createCollector('fake', 'requestRate', 5, 1));
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

        usleep(11e5);

        $detector->check();
        $this->assertTrue($status->ok);
    }

    public function testCheck_RequestRate_MemcachedCollector()
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;

        $detector
            ->setKey('someKey')
            ->declareProcessor('requestRate', function(RequestRateProcessor $processor, Detector $detector) {
                /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
                $processor->setCollector($detector->createCollector(
                    'memcached',
                    'requestRate',
                    5,
                    1,
                    function($collector) {
                        /* @var $collector \Sokil\FraudDetector\Collector\MemcachedCollector */
                        $memcached = new \Memcached();
                        $memcached->addServer('127.0.0.1', 11211);
                        $collector->setMemcached($memcached);
                    }
                ));
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

        usleep(2e6);

        $detector->check();
        $this->assertTrue($status->ok);
    }


    public function getPDOErrorModeList()
    {
        return array(
            array(\PDO::ERRMODE_SILENT),
            array(\PDO::ERRMODE_WARNING),
            array(\PDO::ERRMODE_EXCEPTION),
        );
    }

    /**
     * @dataProvider getPDOErrorModeList
     */
    public function testCheck_RequestRate_PdoMysqlCollector($pdoErrorMode)
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;

        // init pdo connection
        $pdo = new \PDO('mysql:host=localhost;dbname=test', 'root', '');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, $pdoErrorMode);

        // drop in-memory table
        $pdo->query('DROP TABLE IF EXISTS test_collector');

        // configure detector
        $detector
            ->setKey('someKey')
            ->declareProcessor('requestRate', function(RequestRateProcessor $processor, Detector $detector) use($pdo) {
                /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
                $processor->setCollector($detector->createCollector(
                    'pdo_mysql',
                    'requestRate',
                    5,
                    1,
                    function($collector) use($pdo) {
                        /* @var $collector \Sokil\FraudDetector\Collector\PdoMysqlCollector */
                        $collector
                            ->setPdo($pdo)
                            ->setTableName('test_collector')
                            ->setGarbageCollector(1,1);
                    }
                ));
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

        usleep(11e5);

        $detector->check();
        $this->assertTrue($status->ok);

        // drop in-memory table
        $pdo->query('DROP TABLE IF EXISTS test_collector');
    }

    public function getVarTypeList()
    {
        return array(
            array('GLOBALS'),
            array('_GET'),
            array('_POST'),
            array('_SERVER'),
            array('_REQUEST'),
            array('_SESSION'),
            array('_COOKIE'),
            array('_FILES'),
            array('_ENV'),
        );
    }

    /**
     * @dataProvider getVarTypeList
     */
    public function testCheck_Variable($varType)
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;


        $detector
            ->setKey('someKey')
            ->declareProcessor('variable', function($processor) use($varType) {
                /* @var $processor \Sokil\FraudDetector\Processor\VariableProcessor */
                $processor
                    ->setName('globalVariable', $varType)
                    ->equals(42)
                    ->notEquals(500)
                    ->greater(41)
                    ->lower(43);
            })
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($status) {
                $status->ok = false;
            });

        // valid var
        $GLOBALS[$varType]['globalVariable'] = 42;
        $detector->check();
        $this->assertTrue($status->ok);

        // invalid var
        $GLOBALS[$varType]['globalVariable'] = 43;
        $detector->check();
        $this->assertFalse($status->ok);
    }

    public function testCheck_NoProxy()
    {
        $detector = new Detector();

        $status = new \stdClass();
        $status->ok = null;

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
            ->declareProcessor('requestRate', function(RequestRateProcessor $processor, Detector $detector) {
                /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
                $processor->setCollector($detector->createCollector('fake', 'requestRate', 5, 1));
            })
            ->declareProcessor('blackList', function($processor, $detector) {
                /* @var $processor \Sokil\FraudDetector\Processor\BlackListProcessor */
                $processor
                    ->setStorage($detector->createStorage('fake'))
                    ->banOnRateExceed();
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
            ->declareProcessor('blackList', function($processor, $detector) {
                /* @var $processor \Sokil\FraudDetector\Processor\BlackListProcessor */
                $processor->setStorage($detector->createStorage('fake'));
            })
            ->onCheckPassed(function() use($status) {
                $status->ok = true;
            })
            ->onCheckFailed(function() use($status) {
                $status->ok = false;
            });

        $detector->check();
        $this->assertTrue($status->ok);

        $detector->getProcessor('blackList')->ban();

        $detector->check();
        $this->assertFalse($status->ok);

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

    public function testEvents()
    {
        $detector = new Detector;

        $status = new \stdclass;
        $status->value = '';

        $detector->subscribe('event1', function($e) use($status) {
            $status->value .= '[1.10]' . $e->getTarget();
        }, 10);

        $detector->subscribe('event1', function($e) use($status) {
            $status->value .= '[1.20]' . $e->getTarget();
        }, 20);

        $detector->subscribe('event2', function($e) use($status) {
            $status->value .= '[2.10]' . $e->getTarget();

            $e->cancel();
        }, 10);

        $event1 = $detector->trigger('event1', 'target1');
        $this->assertInstanceOf('\Sokil\FraudDetector\Event', $event1);
        $this->assertFalse($event1->isCancelled());

        $event2 = $detector->trigger('event2', 'target2');
        $this->assertTrue($event2->isCancelled());

        $this->assertEquals('[1.20]target1[1.10]target1[2.10]target2', $status->value);
    }
}