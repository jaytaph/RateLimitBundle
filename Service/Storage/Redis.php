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

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }

    public function limitRate($key)
    {
        if (! $this->client->hexists($key, 'limit')) {
            return false;
        }

        $this->client->hincrby($key, 'calls', 1);

        return $this->getRateInfo($key);
    }

    public function createRate($key, $limit, $period)
    {
        $this->client->hset($key, 'limit', $limit);
        $this->client->hset($key, 'calls', 1);
        $this->client->hset($key, 'reset', time() + $period);
        $this->client->expire($key, $period);

        return $this->getRateInfo($key);
    }

    public function resetRate($key)
    {
        $this->client->hdel($key);
    }
}
