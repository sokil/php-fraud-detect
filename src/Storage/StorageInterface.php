<?php

namespace Sokil\FraudDetector\Storage;

interface StorageInterface
{
    public function store($key);
    
    public function isStored($key);
}