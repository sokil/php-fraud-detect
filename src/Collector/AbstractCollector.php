<?php

namespace Sokil\FraudDetector\Collector;

abstract class AbstractCollector implements CollectorInterface
{
    protected $key;

    protected $requestNum;

    protected $timeInterval;

    public function __construct($key, $requestNum, $timeInterval)
    {
        $this->key = $key;

        $this->requestNum = $requestNum;

        $this->timeInterval = $timeInterval;

        $this->init();
    }

    protected function init() {}
}