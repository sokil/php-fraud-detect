<?php

namespace Sokil\FraudDetector;

use Sokil\DataType\PriorityList;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Sokil\FraudDetector\Processor\ProcessorInterface;
use Sokil\FraudDetector\Collector\CollectorInterface;

class Detector
{
    const STATE_UNCHECKED   = 'unckecked';
    const STATE_PASSED      = 'checkPassed';
    const STATE_FAILED      = 'checkFailed';

    private $state = self::STATE_UNCHECKED;

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

    private $collectorNamespaces = array(
        '\Sokil\FraudDetector\Collector',
    );

    private $storageNamespaces = array(
        '\Sokil\FraudDetector\Storage',
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
        $this->state = self::STATE_UNCHECKED;
        
        // check all conditions
        /* @var $processor \Sokil\FraudDetector\ProcessorInterface */
        foreach($this->processorDeclarationList->getKeys() as $processorName) {
            $processor = $this->getProcessor($processorName);

            if($processor->isPassed()) {
                $processor->afterCheckPassed();
                $this->trigger(self::STATE_PASSED . ':' . $processorName);
                
                // check passed if all processors passes their cheks
                if ($this->state === self::STATE_UNCHECKED) {
                    $this->state = self::STATE_PASSED;
                }
            } else {
                $processor->afterCheckFailed();
                $this->trigger(self::STATE_FAILED . ':' . $processorName);
                // if any processor failed - all check failed
                $this->state = self::STATE_FAILED;
            }
        }

        $this->trigger($this->state);
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

    public function addProcssor($name, ProcessorInterface $processor, $priority = 0)
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
     * @return \Sokil\FraudDetector\ProcessorInterface
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

        if (!($processor instanceof ProcessorInterface)) {
            throw new \Exception('Processor must inherit ProcessorInterface');
        }

        // configure processor
        $configuratorCallable = $this->processorDeclarationList->get($processorName);
        if($configuratorCallable && is_callable($configuratorCallable)) {
            call_user_func($configuratorCallable, $processor, $this);
        }

        $this->processorList[$processorName] = $processor;

        return $processor;
    }

    public function registerCollectorNamespace($namespace)
    {
        $this->collectorNamespaces[] = rtrim($namespace, '\\');
        return $this;
    }

    private function getCollectorClassName($type)
    {
        if(false == strpos($type, '_')) {
            $className = ucfirst($type);
        } else {
            $className = implode('', array_map('ucfirst', explode('_', $type)));
        }

        $className .= 'Collector';

        foreach($this->collectorNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                return $fullyQualifiedClassName;
            }
        }

        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }

    /*
     * @param int $requestNumber maximum number of allowed requests
     * @param int $timeInterval time interval in seconds
     */
    public function createCollector(
        $type,
        $namespace,
        $requestNumber,
        $timeInterval,
        $configuratorCallable = null
    ) {
        $className = $this->getCollectorClassName($type);

        $collector = new $className(
            $this->getKey() . ':' . $namespace,
            $requestNumber,
            $timeInterval
        );

        if (!($collector instanceof CollectorInterface)) {
            throw new \Exception('Collector must inherit CollectorInterface');
        }

        // configure
        if(is_callable($configuratorCallable)) {
            call_user_func($configuratorCallable, $collector, $this);
        }

        return $collector;

    }

    private function getStorageClassName($type)
    {
        if(false == strpos($type, '_')) {
            $className = ucfirst($type);
        } else {
            $className = implode('', array_map('ucfirst', explode('_', $type)));
        }

        $className .= 'Storage';

        foreach($this->storageNamespaces as $namespace) {
            $fullyQualifiedClassName = $namespace . '\\' . $className;
            if(class_exists($fullyQualifiedClassName)) {
                return $fullyQualifiedClassName;
            }
        }

        throw new \Exception('Class ' . $fullyQualifiedClassName . ' not found');
    }

    public function createStorage($type, $configuratorCallable = null)
    {
        $className = $this->getStorageClassName($type);

        $storage = new $className();

        // configure
        if(is_callable($configuratorCallable)) {
            call_user_func($configuratorCallable, $storage);
        }

        return $storage;

    }

    public function registerStorageNamespace($namespace)
    {
        $this->storageNamespaces[] = rtrim($namespace, '\\');
        return $this;
    }

    private function on($stateName, $callable)
    {
        if($this->hasState(self::STATE_UNCHECKED)) {
            $this->subscribe($stateName, $callable);
        } elseif($this->hasState($stateName)) {
            call_user_func($callable);
        }

        return $this;
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

    public function subscribe($eventName, $callable, $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $callable, $priority);
        return $this;
    }

    /**
     *
     * @param string $eventName
     * @param mixed $target
     * @return \Sokil\FraudDetector\Event
     */
    public function trigger($eventName, $target = null)
    {
        $event = new Event();

        if($target) {
            $event->setTarget($target);
        }

        return $this->eventDispatcher->dispatch($eventName, $event);
    }
}
