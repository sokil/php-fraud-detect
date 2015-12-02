<?php

namespace Sokil\FraudDetector\Processor;

use Sokil\FraudDetector\Storage\StorageInterface;

class BlackListProcessor extends AbstractProcessor
{
    /**
     * @var \Sokil\FraudDetector\Storage\StorageInterface
     */
    private $storage;

    private $banOnRateExceed = false;

    public function init()
    {
        $self = $this;

        // rate exceed event handler
        $this->detector->subscribe('checkFailed:requestRate', function() use($self) {
            // ban on rate exceed
            if($self->isBannedOnRateExceed()) {
                $self->ban();
            }
        });
    }

    public function isPassed()
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

    public function banOnRateExceed()
    {
        $this->banOnRateExceed = true;
        return $this;
    }

    public function isBannedOnRateExceed()
    {
        return true === $this->banOnRateExceed;
    }

    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
        return $this;
    }
}