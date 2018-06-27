<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Doctrine\Common\Cache\Cache;
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
        $info = $this->client->fetch($key);
        if ($info === false || !array_key_exists('limit', $info)) {
            return false;
        }

        return $this->createRateInfo($info);
    }

    public function limitRate($key)
    {
        $info = $this->client->fetch($key);
        if ($info === false || !array_key_exists('limit', $info)) {
            return false;
        }

        $info['calls']++;

        $expire = $info['reset'] - time();

        $this->client->save($key, $info, $expire);

        return $this->createRateInfo($info);
    }

    public function createRate($key, $limit, $period)
    {
        $info          = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

        $this->client->save($key, $info, $period);

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
