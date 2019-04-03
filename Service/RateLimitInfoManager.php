<?php

namespace Noxlogic\RateLimitBundle\Service;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;

class RateLimitInfoManager
{
    /**
     * @var RateLimitService
     */
    private $rateLimitService;

    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * @param string $key
     * @param RateLimit $rateLimit
     * @return RateLimitInfo|null
     */
    public function getRateLimitInfo($key, RateLimit $rateLimit)
    {
        $rateLimitInfo = $this->rateLimitService->limitRate($key);
        if (!$rateLimitInfo) {
            // Create new rate limit entry for this call
            return $this->rateLimitService->createRate($key, $rateLimit->getLimit(), $rateLimit->getPeriod());
        }

        // Reset the rate limits
        if (time() >= $rateLimitInfo->getResetTimestamp()) {
            $this->rateLimitService->resetRate($key);
            $rateLimitInfo = $this->rateLimitService->createRate($key, $rateLimit->getLimit(), $rateLimit->getPeriod());
        }

        return $rateLimitInfo;
    }
}
