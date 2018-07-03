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

        return $this->createRateInfo($key, $info);
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

        return $this->createRateInfo($key, $info);
    }

    public function createRate($key, $limit, $period)
    {
        $info = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;
        $info['blocked'] = 0;

        $this->client->set($key, $info, $period);

        return $this->createRateInfo($key, $info);
    }

    public function resetRate($key)
    {
        $this->client->delete($key);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function setBlock(RateLimitInfo $rateLimitInfo, $periodBlock)
    {
        $resetTimestamp = time() + $periodBlock;

        $this->client->set(
            $rateLimitInfo->getKey(),
            [
                'limit'   => $rateLimitInfo->getLimit(),
                'calls'   => $rateLimitInfo->getCalls(),
                'reset'   => $resetTimestamp,
                'blocked' => 1,
            ],
            $periodBlock
        );

        $rateLimitInfo->setBlocked(true);
        $rateLimitInfo->setResetTimestamp($resetTimestamp);

        return true;
    }

    private function createRateInfo($key, array $info)
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);
        $rateLimitInfo->setBlocked(isset($info['blocked']) && $info['blocked']);
        $rateLimitInfo->setKey($key);

        return $rateLimitInfo;
    }
}
