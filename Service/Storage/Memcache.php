<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class Memcache implements StorageInterface
{
    /**
     * @var \Memcached
     */
    protected $client;

    public function __construct(\Memcached $client)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        $info = $this->client->get($key);

        return $this->createRateInfo($info);
    }

    public function limitRate($key)
    {
        $cas = null;
        $i = 0;
        do {
            $info = $this->client->get($key, null, $cas);
            if (!$info) {
                return false;
            }

            $info['calls']++;
            $this->client->cas($cas, $key, $info);
        } while ($this->client->getResultCode() == \Memcached::RES_DATA_EXISTS && $i++ < 5);

        return $this->createRateInfo($info);
    }

    public function createRate($key, $limit, $period)
    {
        $info = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

        $this->client->set($key, $info, $period);

        return $this->createRateInfo($info);
    }

    public function resetRate($key)
    {
        $this->client->delete($key);
        return true;
    }

    private function createRateInfo(array $info)
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }
}
