<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Predis\Client;

class Redis implements StorageInterface
{
    /**
     * @var \Predis\Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        $info = $this->client->hgetall($key);
        if (!isset($info['limit']) || !isset($info['calls']) || !isset($info['reset'])) {
            return false;
        }

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);
        $rateLimitInfo->setBlocked(isset($info['blocked']) && $info['blocked']);
        $rateLimitInfo->setKey($key);

        return $rateLimitInfo;
    }

    public function limitRate($key)
    {
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
        $reset = time() + $period;

        $this->client->hset($key, 'limit', $limit);
        $this->client->hset($key, 'calls', 1);
        $this->client->hset($key, 'reset', $reset);
        $this->client->hset($key, 'blocked', 0);
        $this->client->expire($key, $period);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($limit);
        $rateLimitInfo->setCalls(1);
        $rateLimitInfo->setResetTimestamp($reset);
        $rateLimitInfo->setBlocked(false);

        return $rateLimitInfo;
    }

    public function resetRate($key)
    {
        $this->client->del($key);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function setBlock(RateLimitInfo $rateLimitInfo, $periodBlock)
    {
        $resetTimestamp = time() + $periodBlock;
        $this->client->hset($rateLimitInfo->getKey(), 'limit', $rateLimitInfo->getLimit());
        $this->client->hset($rateLimitInfo->getKey(), 'calls', $rateLimitInfo->getCalls());
        $this->client->hset($rateLimitInfo->getKey(), 'reset', $resetTimestamp);
        $this->client->hset($rateLimitInfo->getKey(), 'blocked', 1);
        $this->client->expire($rateLimitInfo->getKey(), $periodBlock);

        $rateLimitInfo->setBlocked(true);
        $rateLimitInfo->setResetTimestamp($resetTimestamp);

        return true;
    }
}
