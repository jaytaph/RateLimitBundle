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

        return $this->createRateInfo($info);
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

        return $this->createRateInfo($info);
    }

    public function createRate($key, $limit, $period)
    {
        $info = [
            'limit' => $limit,
            'calls' => 1,
            'reset' => time() + $period,
        ];
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
