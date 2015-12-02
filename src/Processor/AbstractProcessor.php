<?php

namespace Sokil\FraudDetector\Processor;

use Sokil\FraudDetector\Detector;

abstract class AbstractProcessor implements ProcessorInterface
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

    public function getName()
    {
        $fullyQualifiedClassNameChunks = explode('\\', get_called_class());
        $className = array_pop($fullyQualifiedClassNameChunks);

        // remove "Processor" suffix and lovercase first char
        return lcfirst(substr($className, 0, -9));
    }

    public function afterCheckPassed() {}

    public function afterCheckFailed() {}
}