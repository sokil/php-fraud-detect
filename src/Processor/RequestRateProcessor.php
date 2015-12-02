<?php

namespace Sokil\FraudDetector\Processor;

use Sokil\FraudDetector\Collector\CollectorInterface;

class RequestRateProcessor extends AbstractProcessor
{
    private $requestNumber = 1;

    private $timeInterval = 1;

    /**
     *
     * @var \Sokil\FraudDetector\RequestRate\Collector\CollectorInterface
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

    public function setCollector(CollectorInterface $collector)
    {
        $this->collector = $collector;
        return $this;
    }
}