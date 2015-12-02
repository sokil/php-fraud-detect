<?php

namespace Sokil\FraudDetector\Collector;

/**
 * @property \Memcached $storage Instance of \Memcached storage
 */
class MemcachedCollector extends AbstractCollector
{
    /**
     * @var \Memcached
     */
    private $memcached;

    public function setMemcached(\Memcached $memcached)
    {
        $this->memcached = $memcached;
        return $this;
    }

    public function isRateLimitExceed()
    {
        $requestNum = $this->memcached->get($this->key);

        // is key not exists
        if(\Memcached::RES_NOTFOUND === $this->memcached->getResultCode()) {
            return false;
        }

        // is requests limit reached
        return $requestNum >= $this->requestNum;
    }

    public function collect()
    {
        $newValue = $this->memcached->increment($this->key);
        if(false !== $newValue) {
            return $this;
        }

        if(\Memcached::RES_NOTFOUND !== $this->memcached->getResultCode()) {
            throw new \Exception('Error collecting value');
        }

        $this->memcached->set($this->key, 1, time() + $this->timeInterval);
    }
}