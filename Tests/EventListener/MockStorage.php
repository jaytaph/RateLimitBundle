<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\StorageInterface;

class MockStorage implements StorageInterface
{
    protected $rates;

    /**
     * Get information about the current rate
     *
     * @param  string $key
     * @return RateLimitInfo Rate limit information
     */
    public function getRateInfo($key)
    {
        $info = $this->rates[$key];

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);
        $rateLimitInfo->setLimit($info['limit']);
        return $rateLimitInfo;
    }

    /**
     * Limit the rate by one
     *
     * @param  string $key
     * @return RateLimitInfo Rate limit info
     */
    public function limitRate($key)
    {
        if (! isset($this->rates[$key])) {
            return null;
        }

        $this->rates[$key]['calls']++;
        return $this->getRateInfo($key);
    }

    /**
     * Create a new rate entry
     *
     * @param  string $key
     * @param  integer $limit
     * @param  integer $period
     * @return \Noxlogic\RateLimitBundle\Service\RateLimitInfo
     */
    public function createRate($key, $limit, $period)
    {
        $this->rates[$key] = array('calls' => 1, 'limit' => $limit, 'reset' => (time() + $period));
        return $this->getRateInfo($key);
    }

    /**
     * Reset the rating
     *
     * @param $key
     */
    public function resetRate($key)
    {
        unset($this->rates[$key]);
    }

    public function createMockRate($key, $limit, $period, $calls)
    {
        $this->rates[$key] = array('calls' => $calls, 'limit' => $limit, 'reset' => (time() + $period));
        return $this->getRateInfo($key);
    }
}
