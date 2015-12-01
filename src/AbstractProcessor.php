<?php

namespace Sokil\FraudDetector;

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

    public function process()
    {
        if($this->isPassed()) {
            $this->detector->trigger($this->getName() . '.checkPassed');
            $this->afterCheckPassed();
            return true;
        } else {
            $this->detector->trigger($this->getName() . '.checkFailed');
            $this->afterCheckFailed();
            return false;
        }
    }

    protected function afterCheckPassed() {}

    protected function afterCheckFailed() {}
}