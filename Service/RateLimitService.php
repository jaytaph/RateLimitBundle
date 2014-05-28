<?php

namespace Noxlogic\RateLimitBundle\Service;

use Noxlogic\RateLimitBundle\Service\Storage\StorageInterface;

class RateLimitService
{
    /**
     * @var Storage\StorageInterface
     */
    protected $storage;

    /**
     * @param StorageInterface $storage
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @return StorageInterface
     */
    public function getStorage()
    {
        if (! $this->storage) {
            throw new \RuntimeException('Storage engine must be set prior to using the rate limit service');
        }

        return $this->storage;
    }

    /**
     *
     */
    public function limitRate($key)
    {
        return $this->storage->limitRate($key);
    }

    /**
     *
     */
    public function createRate($key, $limit, $period)
    {
        return $this->storage->createRate($key, $limit, $period);
    }

    /**
     *
     */
    public function resetRate($key)
    {
        return $this->storage->resetRate($key);
    }
}
