<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class Memcache implements StorageInterface
{
    /**
     * @var \Memcached
     */
    protected $client;

    public function __construct(\Memcached $client)
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

        return $this->createRateInfo($info);
    }

    public function limitRate($key)
    {
        $cas = null;
        $i = 0;
        do {
            if (defined('Memcached::GET_EXTENDED')) {
                try {
                    $_o = $this->client->get($key, null, \Memcached::GET_EXTENDED);
                } catch (\Throwable $e) {
                    throw new LimitRateRateLimitStorageException($e);
                }

                $info = $_o['value'] ?? null;
                $cas = $_o['cas'] ?? null;
            } else {
                try {
                    $info = $this->client->get($key, null, $cas);
                } catch (\Throwable $e) {
                    throw new LimitRateRateLimitStorageException($e);
                }
            }
            if (!$info) {
                return false;
            }

            $info['calls']++;
            try {
                $this->client->cas($cas, $key, $info);
            } catch (\Throwable $e) {
                throw new LimitRateRateLimitStorageException($e);
            }
        } while ($this->client->getResultCode() == \Memcached::RES_DATA_EXISTS && $i++ < 5);

        return $this->createRateInfo($info);
    }

    public function createRate($key, $limit, $period)
    {
        $info = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

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

    private function createRateInfo(array $info)
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }
}
