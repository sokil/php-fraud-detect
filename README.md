php-fraud-detect
================

Checker of fraud requests. This component allows you to check if some user do no more requests than allowed, and if limits of requests reched do some tasks like ban user or show captcha.

[![Build Status](https://travis-ci.org/sokil/php-fraud-detect.png?branch=master&1)](https://travis-ci.org/sokil/php-fraud-detect)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-fraud-detect/v/stable.png)](https://packagist.org/packages/sokil/php-fraud-detect)
[![Coverage Status](https://coveralls.io/repos/sokil/php-fraud-detect/badge.png)](https://coveralls.io/r/sokil/php-fraud-detect)

Installation
------------

You can install library through Composer:
```javascript
{
    "require": {
        "sokil/php-fraud-detect": "dev-master"
    }
}
```

### Basic usage

```php
<?php

$detector = new \Sokil\FraudDetector\Detector();
$detector
    // Configure unique user identifier like session id or track id or user ip
    ->setKey(session_id())
    // You can add few processors which execute different checks.
    // Processors may check request from proxy, existance of user in blacklist, etc.
    // This processor check if number of requests reached.
    ->declareProcessor('requestRate', function($processor) {
        /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
        $processor
            // Limit set as 5 requests for one second.
            ->setRequestRate(5, 1)
            // Collector used to store stat of requests
            ->setCollector('memcached', function($collector) {
                /* @var $collector \Sokil\FraudDetector\Collector\MemcachedCollector */
                $memcached = new \Memcached();
                $memcached->addServer('127.0.0.1', 11211);
                $collector->setStorage($memcached);
            });
    })
    ->onCheckPassed(function() use($status) {
        // do something on success request
    })
    ->onCheckFailed(function() use($status) {
        // do something if limits reached
        die('Request limits reached. Please, try again later');
    })
    ->check();
```
