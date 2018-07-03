<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Psr\SimpleCache\CacheInterface;

class SimpleCache implements StorageInterface
{
    /**
     * @var CacheInterface
     */
    protected $client;

    public function __construct(CacheInterface $client)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        $info = $this->client->get($key);
        if ($info === null || !array_key_exists('limit', $info)) {
            return false;
        }

        return $this->createRateInfo($key, $info);
    }

    public function limitRate($key)
    {
        $info = $this->client->get($key);
        if ($info === null || !array_key_exists('limit', $info)) {
            return false;
        }

        $info['calls']++;
        $ttl = $info['reset'] - time();

        $this->client->set($key, $info, $ttl);

        return $this->createRateInfo($key, $info);
    }

    public function createRate($key, $limit, $period)
    {
        $info = [
            'limit'   => $limit,
            'calls'   => 1,
            'reset'   => time() + $period,
            'blocked' => 0
        ];
        $this->client->set($key, $info, $period);

        return $this->createRateInfo($key, $info);
    }

    public function resetRate($key)
    {
        $this->client->delete($key);

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

    /**
     * @inheritDoc
     */
    public function setBlock(RateLimitInfo $rateLimitInfo, $periodBlock)
    {
        $resetTimestamp = time() + $periodBlock;
        $info = [
            'limit'   => $rateLimitInfo->getLimit(),
            'calls'   => $rateLimitInfo->getCalls(),
            'reset'   => $resetTimestamp,
            'blocked' => 1
        ];
        if (!$this->client->set($rateLimitInfo->getKey(), $info, $periodBlock)) {
            return false;
        }

        $rateLimitInfo->setBlocked(true);
        $rateLimitInfo->setResetTimestamp($resetTimestamp);

        return true;
    }
}
