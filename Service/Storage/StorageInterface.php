<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

interface StorageInterface
{
    /**
     * Get information about the current rate
     *
     * @param  string        $key
     * @return RateLimitInfo|bool Rate limit information or false on error
     */
    public function getRateInfo($key);

    /**
     * Limit the rate by one
     *
     * @param  string        $key
     * @return RateLimitInfo|bool Rate limit info or false on error
     */
    public function limitRate($key);

    /**
     * Create a new rate entry
     *
     * @param  string        $key
     * @param  integer       $limit
     * @param  integer       $period
     *
     * @return RateLimitInfo|bool Rate limit info or false on error
     */
    public function createRate($key, $limit, $period);

    /**
     * Reset the rating
     *
     * @param $key
     *
     * @return bool false on error
     */
    public function resetRate($key);
}
