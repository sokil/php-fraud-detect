<?php

namespace Sokil\FraudDetector;

use Sokil\DataType\PriorityList;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Detector
{
    const STATE_UNCHECKED   = 'unckecked';
    const STATE_PASSED      = 'checkPassed';
    const STATE_FAILED      = 'checkFailed';

    private $state = self::STATE_UNCHECKED;

    private $handlers = array();

    /**
     *
     * @var mixed key to identify unique user
     */
    private $key;

    /**
     *
     * @var \Sokil\DataType\PriorityList
     */
    private $processorDeclarationList;

    private $processorList = array();

    private $processorNamespaces = array(
        '\Sokil\FraudDetector\Processor',
    );

    /**
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct()
    {
        $this->processorDeclarationList = new PriorityList();
        $this->eventDispatcher = new EventDispatcher();
    }

    /**
     * Key that uniquely identify user
     * @param type $key
     * @return \Sokil\FraudDetector\Detector
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    public function getKey()
    {
        if(!$this->key) {
            $this->key = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        }

        return $this->key;
    }

    /**
     * Check if request is not fraud
     */
    public function check()
    {
        // check all conditions
        /* @var $processor \Sokil\FraudDetector\AbstractProcessor */
        foreach($this->processorDeclarationList->getKeys() as $processorName) {
            if($this->getProcessor($processorName)->process()) {
                $this->setState(self::STATE_PASSED);
            } else {
                $this->setState(self::STATE_FAILED);
            }
        }

        $this->callDelayedHandlers();
    }

    public function registerProcessorNamespace($namespace)
    {
        $this->processorNamespaces[] = rtrim($namespace, '\\');
        return $this;
    }

    /**
     * Add processor identified by its name.
     * If processor already added, it will be replaced by new instance.
     *
     * @param string $name name of processor
     * @param callable $callable configurator callable
     * @return \Sokil\FraudDetector\Detector
     */
    public function declareProcessor($name, $callable = null, $priority = 0)
    {
        $this->processorDeclarationList->set($name, $callable, $priority);
        return $this;
    }

    public function addProcssor($name, AbstractProcessor $processor, $priority = 0)
    {
        $this->declareProcessor($name, null, $priority);
        $this->processorList[$name] = $processor;

        return $this;
    }

    public function isProcessorDeclared($name)
    {
        return $this->processorDeclarationList->has($name);
    }

    /**
     * Factory method to create new check condition
     *
     * @param string $name name of check condition
     * @return \Sokil\FraudDetector\AbstractProcessor
     * @throws \Exception
     */
    private function getProcessorClassName($name)
    {
        $className = ucfirst($name) . 'Processor';

        foreach($this->processorNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                return $fullyQualifiedClassName;
            }
        }

        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }

    public function getProcessor($processorName)
    {
        if(isset($this->processorList[$processorName])) {
            return $this->processorList[$processorName];
        }

        // create processor
        $processorClassName = $this->getProcessorClassName($processorName);
        $processor =  new $processorClassName($this);

        // configure processor
        $configuratorCallable = $this->processorDeclarationList->get($processorName);
        if($configuratorCallable && is_callable($configuratorCallable)) {
            call_user_func($configuratorCallable, $processor);
        }

        $this->processorList[$processorName] = $processor;

        return $processor;
    }

    public function onCheckPassed($callable)
    {
        $this->on(self::STATE_PASSED, $callable);

        return $this;
    }

    public function onCheckFailed($callable)
    {
        $this->on(self::STATE_FAILED, $callable);

        return $this;
    }

    public function isUnchecked()
    {
        return $this->hasState(self::STATE_UNCHECKED);
    }

    public function isPassed()
    {
        return $this->hasState(self::STATE_PASSED);
    }

    public function isFailed()
    {
        return $this->hasState(self::STATE_FAILED);
    }

    private function hasState($state)
    {
        return $this->state === $state;
    }

    private function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    private function on($stateName, $callable)
    {
        if($this->hasState(self::STATE_UNCHECKED)) {
            $this->delayHandler($stateName, $callable);
        } elseif($this->hasState($stateName)) {
            $this->callHandler($callable);
        }

        return $this;
    }

    private function callHandler($callable)
    {
        call_user_func($callable);
        return $this;
    }

    private function delayHandler($stateName, $callable)
    {
        $this->handlers[$stateName][] = $callable;
        return $this;
    }

    private function callDelayedHandlers()
    {
        if(empty($this->handlers[$this->state])) {
            return $this;
        }

        foreach($this->handlers[$this->state] as $callable) {
            $this->callHandler($callable);
        }

        return $this;
    }

    public function subscribe($eventName, $callable, $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $callable, $priority);
        return $this;
    }

    public function trigger($eventName, $target = null)
    {
        $event = new Event();

        if($target) {
            $event->setTarget($target);
        }

        $this->eventDispatcher->dispatch($eventName, $event);
        return $this;
    }
}