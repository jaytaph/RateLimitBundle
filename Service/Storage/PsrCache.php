<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\StorageInterface;
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
        $item = $this->client->getItem($key);
        if (!$item->isHit()) {
            return false;
        }

        return $this->createRateInfo($item->get());
    }

    public function limitRate($key)
    {
        $item = $this->client->getItem($key);
        if (!$item->isHit()) {
            return false;
        }

        $info = $item->get();

        $info['calls']++;
        $item->set($info);
        $item->expiresAfter($info['reset'] - time());

        $this->client->save($item);

        return $this->createRateInfo($info);
    }

    public function createRate($key, $limit, $period)
    {
        $info = [
            'limit' => $limit,
            'calls' => 1,
            'reset' => time() + $period,
        ];
        $item = $this->client->getItem($key);
        $item->set($info);
        $item->expiresAfter($period);

        $this->client->save($item);

        return $this->createRateInfo($info);
    }

    public function resetRate($key)
    {
        $this->client->deleteItem($key);

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
