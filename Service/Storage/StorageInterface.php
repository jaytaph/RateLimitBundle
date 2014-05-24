<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

interface StorageInterface {

    /**
     * Get information about the current rate
     *
     * @param string $key
     * @return RateLimitInfo Rate limit information
     */
    function getRateInfo($key);

    /**
     * Limit the rate by one
     *
     * @param string $key
     * @return RateLimitInfo Rate limit info
     */
    function limitRate($key);

    /**
     * Reset the rating
     *
     * @param $key
     */
    function resetRate($key);

}
