<?php

namespace Sokil\FraudDetector\Processor;

class RequestRateProcessor extends \Sokil\FraudDetector\AbstractProcessor
{
    private $requestNumber = 1;

    private $timeInterval = 1;

    /**
     *
     * @var \Sokil\FraudDetector\Processor\RequestRate\AbstractCollector
     */
    private $collector;

    protected function isPassed()
    {
        return !$this->collector->isRateLimitExceed();
    }

    protected function afterCheckPassed()
    {
        $this->collector->collect();
        return $this;
    }

    protected function afterCheckFailed()
    {
        $this->detector->trigger('requestRate.rateExceed');

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

    public function setCollector($type, $configuratorCallable = null)
    {
        $className = '\Sokil\FraudDetector\Collector\\' . ucfirst($type) . 'Collector';

        if(!class_exists($className)) {
            throw new \Exception('Collector ' . $type . ' not found');
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