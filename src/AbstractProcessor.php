<?php

namespace Sokil\FraudDetector;

abstract class AbstractProcessor
{
    /**
     *
     * @var \Sokil\FraudDetector\Detector
     */
    protected $detector;

    protected function init() {}

    public function __construct(Detector $detector)
    {
        $this->detector = $detector;

        $this->init();
    }

    public function process()
    {
        if($this->isPassed()) {
            $this->afterCheckPassed();
            return true;
        } else {
            $this->afterCheckFailed();
            return false;
        }
    }

    abstract protected function isPassed();

    protected function afterCheckPassed() {}

    protected function afterCheckFailed() {}
}