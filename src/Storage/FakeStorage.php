<?php

namespace Sokil\FraudDetector\Storage;

class FakeStorage extends AbstractStorage
{
    private $fakeStorage = array();

    public function store($key)
    {
        $this->fakeStorage[] = $key;
        return $this;
    }

    public function isStored($key)
    {
        return in_array($key, $this->fakeStorage);
    }
}