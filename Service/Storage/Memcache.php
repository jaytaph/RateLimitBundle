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
        // @codeCoverageIgnoreStart
    }

    public function limitRate($key)
    {
        // @codeCoverageIgnoreStart
    }

    public function createRate($key, $limit, $period)
    {
        // @codeCoverageIgnoreStart
    }

    public function resetRate($key)
    {
        // @codeCoverageIgnoreStart
    }
}
