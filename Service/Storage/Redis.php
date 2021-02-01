<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Predis\ClientInterface;

class Redis implements StorageInterface
{
    /**
     * @var \Predis\ClientInterface
     */
    protected $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        $key = $this->sanitizeRedisKey($key);

        $info = $this->client->hgetall($key);
        if (!isset($info['limit']) || !isset($info['calls']) || !isset($info['reset'])) {
            return false;
        }

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }

    public function limitRate($key)
    {
        $key = $this->sanitizeRedisKey($key);

        $info = $this->getRateInfo($key);
        if (!$info) {
            return false;
        }

        $calls = $this->client->hincrby($key, 'calls', 1);
        $info->setCalls($calls);

        return $info;
    }

    public function createRate($key, $limit, $period)
    {
        $key = $this->sanitizeRedisKey($key);

        $reset = time() + $period;

        $this->client->hset($key, 'limit', $limit);
        $this->client->hset($key, 'calls', 1);
        $this->client->hset($key, 'reset', $reset);
        $this->client->expire($key, $period);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($limit);
        $rateLimitInfo->setCalls(1);
        $rateLimitInfo->setResetTimestamp($reset);

        return $rateLimitInfo;
    }

    public function resetRate($key)
    {
        $key = $this->sanitizeRedisKey($key);

        $this->client->del($key);

        return true;
    }

    /**
     * Sanitizies key so it can be used safely in REDIS
     *
     * @param $key
     * @return string|string[]
     */
    protected function sanitizeRedisKey($key) {
        return str_replace(str_split('@{}()/\:'), '_', $key);
    }

}
