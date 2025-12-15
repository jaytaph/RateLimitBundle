<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
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
        try {
            $info = $this->client->get($key);
        } catch (\Throwable $e) {
            throw new GetRateInfoRateLimitStorageException($e);
        }

        if ($info === null || !array_key_exists('limit', $info)) {
            return false;
        }

        return $this->createRateInfo($info);
    }

    public function limitRate($key)
    {
        try {
            $info = $this->client->get($key);
        } catch (\Throwable $e) {
            throw new LimitRateRateLimitStorageException($e);
        }

        if ($info === null || !array_key_exists('limit', $info)) {
            return false;
        }

        $info['calls']++;
        $ttl = $info['reset'] - time();

        try {
            $this->client->set($key, $info, $ttl);
        } catch (\Throwable $e) {
            throw new LimitRateRateLimitStorageException($e);
        }

        return $this->createRateInfo($info);
    }

    public function createRate($key, $limit, $period)
    {
        $info = [
            'limit' => $limit,
            'calls' => 1,
            'reset' => time() + $period,
        ];

        try {
            $this->client->set($key, $info, $period);
        } catch (\Throwable $e) {
            throw new CreateRateRateLimitStorageException($e);
        }

        return $this->createRateInfo($info);
    }

    public function resetRate($key)
    {
        try {
            $this->client->delete($key);
        } catch (\Throwable $e) {
            throw new ResetRateRateLimitStorageException($e);
        }

        return true;
    }

    private function createRateInfo(array $info): RateLimitInfo
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }
}
