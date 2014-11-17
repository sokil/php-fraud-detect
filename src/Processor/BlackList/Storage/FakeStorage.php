<?php

namespace Sokil\FraudDetector\Processor\BlackList\Storage;

use Sokil\FraudDetector\Processor\BlackList\AbstractStorage;

class FakeStorage extends AbstractStorage
{
    static $fakeStorage = array();
    
    public function store($key)
    {
        self::$fakeStorage[] = $key;
        return $this;
    }
    
    public function isStored($key)
    {
        return in_array($key, self::$fakeStorage);
    }
}