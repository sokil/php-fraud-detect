<?php

namespace Sokil\FraudDetector\Processor;

use \Sokil\FraudDetector\AbstractProcessor;

class RequestRateProcessor extends AbstractProcessor
{
    private $requestNumber = 1;

    private $timeInterval = 1;

    /**
     *
     * @var \Sokil\FraudDetector\CollectorInterface
     */
    private $collector;

    public function isPassed()
    {
        return !$this->collector->isRateLimitExceed();
    }

    public function afterCheckPassed()
    {
        $this->collector->collect();
        return $this;
    }

    /**
     * Define time interval and maximum allowed request number on it
     * @param int $requestNumber maximum number of allowed requests
     * @param int $timeInterval time interval in seconds
     * @return \Sokil\FraudDetector\Processor\RequestRateProcessor
     */
    public function setRequestRate($requestNumber, $timeInterval)
    {
        $this->requestNumber = $requestNumber;
        $this->timeInterval = $timeInterval;
        return $this;
    }

    private function getCollectorClassByType($type)
    {
        if(false == strpos($type, '_')) {
            $className = ucfirst($type);
        } else {
            $className = implode('', array_map('ucfirst', explode('_', $type)));
        }

        return '\Sokil\FraudDetector\Collector\\' . $className . 'Collector';
    }

    public function setCollector($type, $configuratorCallable = null)
    {
        $className = $this->getCollectorClassByType($type);

        if(!class_exists($className)) {
            throw new \Exception('Collector ' . $className . ' not found');
        }

        $this->collector = new $className(
            $this->detector->getKey(),
            $this->requestNumber,
            $this->timeInterval
        );

        // configure
        if(is_callable($configuratorCallable)) {
            call_user_func($configuratorCallable, $this->collector);
        }

        return $this;

    }
}