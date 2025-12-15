<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Doctrine\Common\Cache\Cache;
use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class DoctrineCache implements StorageInterface {

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $client;

    public function __construct(Cache $client)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        try {
            $info = $this->client->fetch($key);
        } catch (\Throwable $e) {
            throw new GetRateInfoRateLimitStorageException($e);
        }

        if ($info === false || !array_key_exists('limit', $info)) {
            return false;
        }

        return $this->createRateInfo($info);
    }

    public function limitRate($key)
    {
        try {
            $info = $this->client->fetch($key);
        } catch (\Throwable $e) {
            throw new LimitRateRateLimitStorageException($e);
        }

        if ($info === false || !array_key_exists('limit', $info)) {
            return false;
        }

        $info['calls']++;

        $expire = $info['reset'] - time();

        try {
            $this->client->save($key, $info, $expire);
        } catch (\Throwable $e) {
            throw new LimitRateRateLimitStorageException($e);
        }

        return $this->createRateInfo($info);
    }

    public function createRate($key, $limit, $period)
    {
        $info          = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

        try {
            $this->client->save($key, $info, $period);
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

    private function createRateInfo(array $info)
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }
}
