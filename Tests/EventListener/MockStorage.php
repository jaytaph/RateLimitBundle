<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\StorageInterface;

class MockStorage implements StorageInterface
{
    private array $rates;

    public function getRateInfo($key): RateLimitInfo
    {
        $info = $this->rates[$key];

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);
        $rateLimitInfo->setLimit($info['limit']);
        return $rateLimitInfo;
    }

    public function limitRate($key): ?RateLimitInfo
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
     */
    public function createRate($key, $limit, $period): RateLimitInfo
    {
        $this->rates[$key] = array('calls' => 1, 'limit' => $limit, 'reset' => (time() + $period));
        return $this->getRateInfo($key);
    }

    public function resetRate($key): void
    {
        unset($this->rates[$key]);
    }

    public function createMockRate($key, $limit, $period, $calls)
    {
        $this->rates[$key] = array('calls' => $calls, 'limit' => $limit, 'reset' => (time() + $period));
        return $this->getRateInfo($key);
    }
}
