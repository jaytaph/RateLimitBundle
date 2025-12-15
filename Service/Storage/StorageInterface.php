<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\RateLimitStorageExceptionInterface;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

interface StorageInterface
{
    /**
     * Get information about the current rate
     *
     * @param  string               $key
     * @return RateLimitInfo|bool   Rate limit information
     * @todo: Replace return type with RateLimitInfo|false when PHP 8.2 is the minimum version
     *
     * @throws RateLimitStorageExceptionInterface
     */
    public function getRateInfo($key);

    /**
     * Limit the rate by one
     *
     * @param  string               $key
     * @return RateLimitInfo|bool   Rate limit info
     * @todo: Replace return type with RateLimitInfo|false when PHP 8.2 is the minimum version
     *
     * @throws RateLimitStorageExceptionInterface
     */
    public function limitRate($key);

    /**
     * Create a new rate entry
     *
     * @param  string        $key
     * @param  integer       $limit
     * @param  integer       $period
     *
     * @throws RateLimitStorageExceptionInterface
     */
    public function createRate($key, $limit, $period);

    /**
     * Reset the rating
     *
     * @param $key
     *
     * @throws RateLimitStorageExceptionInterface
     */
    public function resetRate($key);
}
