<?php

namespace Sokil\FraudDetector;

interface ProcessorInterface
{
    public function __construct(Detector $detector);

    public function getName();

    public function afterCheckPassed();

    public function afterCheckFailed();

    public function isPassed();
}