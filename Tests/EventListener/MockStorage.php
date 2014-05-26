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
        print "LimitRate($key)\n";
        if (! isset($this->rates[$key])) {
            print "Notfound\n";
            return null;
        }

        print $this->rates[$key]['reset'] . " / ". time() . "\n";
        if ($this->rates[$key]['reset'] <= time()) {
            print "Expired\n";
            unset($this->rates[$key]);
            return null;
        }

        $this->rates[$key]['calls']++;
        print "Found: Calls: " . $this->rates[$key]['calls']."\n";
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
        print "CreateRate($key)\n";
        $this->rates[$key] = array('calls' => 1, 'limit' => $limit, 'reset' => (time() + $period));
        print_r($this->rates[$key]);
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

}
