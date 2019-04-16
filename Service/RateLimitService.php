<?php

namespace Noxlogic\RateLimitBundle\Service;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
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


    /**
     * @param string $key
     * @param RateLimit $rateLimit
     * @return RateLimitInfo|null
     */
    public function getRateLimitInfo($key, RateLimit $rateLimit)
    {
        $rateLimitInfo = $this->limitRate($key);
        if (!$rateLimitInfo) {
            // Create new rate limit entry for this call
            return $this->createRate($key, $rateLimit->getLimit(), $rateLimit->getPeriod());
        }

        // Reset the rate limits
        if (time() >= $rateLimitInfo->getResetTimestamp()) {
            $this->resetRate($key);
            $rateLimitInfo = $this->createRate($key, $rateLimit->getLimit(), $rateLimit->getPeriod());
        }

        return $rateLimitInfo;
    }
}
