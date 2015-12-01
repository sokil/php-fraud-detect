<?php

namespace Sokil\FraudDetector;

interface CollectorInterface
{
    public function __construct($key, $requestNum, $timeInterval);

    protected function init() {}

    public function setStorage($storage);

    public function collect();

    public function isRateLimitExceed();
}