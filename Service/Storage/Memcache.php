<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

class Memcache implements StorageInterface
{

    public function __construct()
    {
        throw new \RuntimeException("The memcache storage is not implemented yet.");
    }

    public function getRateInfo($key)
    {
        // TODO: Implement getInfo() method.
    }

    public function limitRate($key)
    {
        // TODO: Implement limit() method.
    }

    public function resetRate($key)
    {
        // TODO: Implement resetRateLimit() method.
    }

}
