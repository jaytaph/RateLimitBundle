<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class Memcache implements StorageInterface
{

    /** @var \Memcached */
    protected $client;

    public function __construct(\Memcached $client)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        $info = $this->client->get($key);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }

    public function limitRate($key)
    {
        $info = $this->client->get($key);
        if ($info == false || !isset($info['limit'])) {
            return;
        }

        $info['calls']++;
        $expire = $info['reset'] - time();

        $this->client->set($key, $info, $expire);

        return $this->getRateInfo($key);
    }

    public function createRate($key, $limit, $period)
    {
        $info = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

        $this->client->set($key, $info, $period);

        return $this->getRateInfo($key);
    }

    public function resetRate($key)
    {
        $this->client->delete($key);
        return true;
    }
}
