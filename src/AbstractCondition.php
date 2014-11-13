<?php

namespace Sokil\FraudDetector;

abstract class AbstractCondition
{
    abstract public function isPassed();
}