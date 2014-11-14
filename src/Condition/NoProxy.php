<?php

namespace Sokil\FraudDetector\Condition;

class NoProxy extends \Sokil\FraudDetector\AbstractCondition
{
    private $proxyHeaders = array(
        'CLIENT_IP',
        'HTTP_CLIENT_IP',
        'X_FORWARDED',
        'FORWARDED_FOR',
        'HTTP_FORWARDED_FOR_IP',
        'FORWARDED',
        'HTTP_X_FORWARDED_FOR',
        'X_FORWARDED_FOR',
        'HTTP_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED',
        'HTTP_PROXY_CONNECTION',
        'FORWARDED_FOR_IP',
        'HTTP_VIA',
        'VIA',
    );
    
    public function isPassed()
    {
        return (bool) array_intersect(array_keys($_SERVER), $this->proxyHeaders);
    }
}