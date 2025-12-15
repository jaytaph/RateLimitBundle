<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Psr\Cache\CacheItemPoolInterface;

class PsrCache implements StorageInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $client;

    public function __construct(CacheItemPoolInterface $client)
    {
        $this->client = $client;
    }

    public function getRateInfo($key)
    {
        try {
            $item = $this->client->getItem($key);
        } catch (\Throwable $e) {
            throw new GetRateInfoRateLimitStorageException($e);
        }

        if (!$item->isHit()) {
            return false;
        }

        return $this->createRateInfo($item->get());
    }

    public function limitRate($key)
    {
        try {
            $item = $this->client->getItem($key);
        } catch (\Throwable $e) {
            throw new LimitRateRateLimitStorageException($e);
        }

        if (!$item->isHit()) {
            return false;
        }

        $info = $item->get();

        $info['calls']++;
        $item->set($info);
        $item->expiresAfter($info['reset'] - time());

        try {
            $this->client->save($item);
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
            $item = $this->client->getItem($key);
        } catch (\Throwable $e) {
            throw new CreateRateRateLimitStorageException($e);
        }

        $item->set($info);
        $item->expiresAfter($period);

        try {
            $this->client->save($item);
        } catch (\Throwable $e) {
            throw new CreateRateRateLimitStorageException($e);
        }

        return $this->createRateInfo($info);
    }

    public function resetRate($key)
    {
        try {
            $this->client->deleteItem($key);
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
