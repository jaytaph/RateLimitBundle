<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

class Memcache implements StorageInterface
{

    function __construct() {
        throw new \RuntimeException("The memcache storage is not implemented yet.");
    }

    function getRateInfo($key)
    {
        // TODO: Implement getInfo() method.
    }

    function limitRate($key)
    {
        // TODO: Implement limit() method.
    }

    function resetRate($key)
    {
        // TODO: Implement resetRateLimit() method.
    }

}
