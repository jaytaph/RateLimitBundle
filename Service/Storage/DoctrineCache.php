<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Doctrine\Common\Cache\Cache;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class DoctrineCache implements StorageInterface {
    /** @var \Doctrine\Common\Cache\Cache */
    protected $client;

    public function __construct(Cache $client = null) {
        $this->client = $client;
    }

    public function getRateInfo($key) {
        if (!$this->client) {
            return false;
        }

        $info = $this->client->fetch($key);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }

    public function limitRate($key) {
        if (!$this->client) {
            return false;
        }

        $info = $this->client->fetch($key);
        if ($info === false || !array_key_exists('limit', $info)) {
            return false;
        }

        $info['calls']++;

        $expire = $info['reset'] - time();

        $this->client->save($key, $info, $expire);

        return $this->getRateInfo($key);
    }

    public function createRate($key, $limit, $period) {
        if (!$this->client) {
            return false;
        }

        $info          = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

        $this->client->save($key, $info, $period);

        return $this->getRateInfo($key);
    }

    public function resetRate($key) {
        if (!$this->client) {
            return false;
        }

        $this->client->delete($key);
        return true;
    }
}
