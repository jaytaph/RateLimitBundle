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
        // @codeCoverageIgnoreStart
    }

    public function limitRate($key)
    {
        // TODO: Implement limit() method.
        // @codeCoverageIgnoreStart
    }

    public function createRate($key, $limit, $period)
    {
        // TODO: Implement createRate() method.
        // @codeCoverageIgnoreStart
    }

    public function resetRate($key)
    {
        // TODO: Implement resetRateLimit() method.
        // @codeCoverageIgnoreStart
    }

}
