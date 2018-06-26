<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class Memcache implements StorageInterface
{

    /** @var \Memcached */
    protected $client;

    public function __construct(\Memcached $client = null)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        if (!$this->client) {
            return false;
        }

        $info = $this->client->get($key);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }

    public function limitRate($key)
    {
        if (!$this->client) {
            return false;
        }

        $cas = null;
        do {
            $info = $this->client->get($key, null, $cas);
            if (!$info) {
                return false;
            }

            $info['calls']++;
            $this->client->cas($cas, $key, $info);
        } while ($this->client->getResultCode() != \Memcached::RES_SUCCESS);

        return $this->getRateInfo($key);
    }

    public function createRate($key, $limit, $period)
    {
        if (!$this->client) {
            return false;
        }

        $info = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

        $this->client->set($key, $info, $period);

        return $this->getRateInfo($key);
    }

    public function resetRate($key)
    {
        if (!$this->client) {
            return false;
        }

        $this->client->delete($key);
        return true;
    }
}
