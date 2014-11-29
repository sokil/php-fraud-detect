<?php

namespace Sokil\FraudDetector\Processor;

class BlackListProcessor extends \Sokil\FraudDetector\AbstractProcessor
{
    /**
     *
     * @var \Sokil\FraudDetector\Processor\BlackList\AbstractStorage
     */
    private $storage;

    protected function isPassed()
    {
        return !$this->isBanned();
    }

    public function ban()
    {
        $this->storage->store($this->detector->getKey());
        return $this;
    }

    public function isBanned()
    {
        return $this->storage->isStored($this->detector->getKey());
    }

    public function setStorage($type, $configuratorCallable = null)
    {
        $className = '\Sokil\FraudDetector\Processor\BlackList\Storage\\' . ucfirst($type) . 'Storage';

        if(!class_exists($className)) {
            throw new \Exception('Storage ' . $type . ' not found');
        }

        $this->storage = new $className();

        // configure
        if(is_callable($configuratorCallable)) {
            call_user_func($configuratorCallable, $this->storage);
        }

        return $this;

    }
}