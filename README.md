php-fraud-detect
================

Checker of fraud requests. This component allows you to check if some user do no more requests than allowed, and if limits of requests reched do some tasks like ban user or show captcha. Read mote about [Token bucket](https://en.wikipedia.org/wiki/Token_bucket).

[![Build Status](https://travis-ci.org/sokil/php-fraud-detect.png?branch=master&1)](https://travis-ci.org/sokil/php-fraud-detect)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-fraud-detect/v/stable.png)](https://packagist.org/packages/sokil/php-fraud-detect)
[![Coverage Status](https://coveralls.io/repos/sokil/php-fraud-detect/badge.png)](https://coveralls.io/r/sokil/php-fraud-detect)
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/sokil/php-fraud-detect?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Total Downloads](http://img.shields.io/packagist/dt/sokil/php-fraud-detect.svg)](https://packagist.org/packages/sokil/php-fraud-detect)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sokil/php-fraud-detect/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sokil/php-fraud-detect/?branch=master)

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

If there are not enought functionality of your frontend server, like [Nginx request limits](http://nginx.org/ru/docs/http/ngx_http_limit_req_module.html), and you want to customize detection of fraud requests, this library is for you.

```php
<?php

$detector = new \Sokil\FraudDetector\Detector();
$detector
    // Configure unique user identifier like session id or track id or user ip.
    // This key defines scope of checking. It may limit check on concrete request, by session or globally
    // by user. So you can set key as concatenation of different parameters, e.g. $_SERVER['REQUEST_URE'] . session_id().
    ->setKey(session_id())
    // You can add few processors which execute different checks.
    // Processors may check request from proxy, existance of user in blacklist, etc.
    // This processor check if number of requests reached.
    ->declareProcessor('requestRate', function($processor, $detector) {
        /* @var $processor \Sokil\FraudDetector\Processor\RequestRateProcessor */
        /* @var $detector \Sokil\FraudDetector\Processor\Detector */
        $processor
            // Limit set as 5 requests for one second.
            // Collector used to store stat of requests
            ->setCollector($detector->createCollector(
                'memcached', // collector type
                'requestRate', // namespace
                5, // requests
                1, // time interval in seconds
                function($collector) {
                    /* @var $collector \Sokil\FraudDetector\Collector\MemcachedCollector */
                    $memcached = new \Memcached();
                    $memcached->addServer('127.0.0.1', 11211);
                    $collector->setStorage($memcached);
                }
            ));
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

### Custom processors

You can write your own processor. It must extend `\Sokil\FraudDetector\AbstractProcessor` class. Just register your processor's namespace and configure it:

```php
<?php

$detector = new \Sokil\FraudDetector\Detector();
$detector
    ->registerProcessorNamespace('\Acme\FraudDetecotor\Processor')
    ->declareProcessor('customProcessor', function($processor) {});
```

All processorts seeking among registered namespaces according to priority of their registration.
