<?php

namespace Sokil\FraudDetector\Collector;

use \Sokil\FraudDetector\AbstractCollector;

/**
 * @property \Memcached $storage Instance of \Memcached storage
 */
class MemcachedCollector extends AbstractCollector
{
    public function isRateLimitExceed()
    {
        $requestNum = $this->storage->get($this->key);

        // is key not exists
        if(\Memcached::RES_NOTFOUND === $this->storage->getResultCode()) {
            return false;
        }

        // is requests limit reached
        return $requestNum >= $this->requestNum;
    }

    public function collect()
    {
        $newValue = $this->storage->increment($this->key);
        if(false !== $newValue) {
            return $this;
        }

        if(\Memcached::RES_NOTFOUND !== $this->storage->getResultCode()) {
            throw new \Exception('Error collecting value');
        }

        $this->storage->set($this->key, 1, time() + $this->timeInterval);

    }
}