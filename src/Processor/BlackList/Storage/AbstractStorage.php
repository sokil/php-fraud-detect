<?php

namespace Sokil\FraudDetector\Processor\BlackList\Storage;

abstract class AbstractStorage
{
    abstract public function store($key);
    
    abstract public function isStored($key); 
}