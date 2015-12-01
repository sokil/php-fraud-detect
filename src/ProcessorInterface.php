<?php

namespace Sokil\FraudDetector;

interface ProcessorInterface
{
    public function __construct(Detector $detector);

    public function getName();

    public function process();

    protected function isPassed();

    protected function afterCheckPassed() {}

    protected function afterCheckFailed() {}
}