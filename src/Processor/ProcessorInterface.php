<?php

namespace Sokil\FraudDetector\Processor;

use Sokil\FraudDetector\Detector;

interface ProcessorInterface
{
    public function __construct(Detector $detector);

    public function getName();

    public function afterCheckPassed();

    public function afterCheckFailed();

    public function isPassed();
}