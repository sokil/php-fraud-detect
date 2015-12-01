<?php

namespace Sokil\FraudDetector;

interface CollectorInterface
{
    public function __construct($key, $requestNum, $timeInterval);

    public function setStorage($storage);

    public function collect();

    public function isRateLimitExceed();
}