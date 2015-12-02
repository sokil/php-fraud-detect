<?php

namespace Sokil\FraudDetector\Collector;

interface CollectorInterface
{
    public function __construct($key, $requestNum, $timeInterval);

    public function collect();

    public function isRateLimitExceed();
}