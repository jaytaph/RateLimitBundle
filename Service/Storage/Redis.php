<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
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

        try {
            $info = $this->client->hgetall($key);
        } catch (\Throwable $e) {
            throw new GetRateInfoRateLimitStorageException($e);
        }

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

        try {
            $info = $this->getRateInfo($key);
            // We want to make sure we throw the exception with the proper class
        } catch (GetRateInfoRateLimitStorageException $e) {
            throw new LimitRateRateLimitStorageException($e->getPrevious());
        }

        if (!$info) {
            return false;
        }

        try {
            $calls = $this->client->hincrby($key, 'calls', 1);
        } catch (\Throwable $e) {
            throw new LimitRateRateLimitStorageException($e);
        }

        $info->setCalls($calls);

        return $info;
    }

    public function createRate($key, $limit, $period)
    {
        $key = $this->sanitizeRedisKey($key);

        $reset = time() + $period;

        try {
            $this->client->hset($key, 'limit', (string) $limit);
            $this->client->hset($key, 'calls', '1');
            $this->client->hset($key, 'reset', (string) $reset);
            $this->client->expire($key, $period);
        } catch (\Throwable $e) {
            throw new CreateRateRateLimitStorageException($e);
        }

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($limit);
        $rateLimitInfo->setCalls(1);
        $rateLimitInfo->setResetTimestamp($reset);

        return $rateLimitInfo;
    }

    public function resetRate($key)
    {
        $key = $this->sanitizeRedisKey($key);

        try {
            $this->client->del($key);
        } catch (\Throwable $e) {
            throw new ResetRateRateLimitStorageException($e);
        }

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
