<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;

use Doctrine\Common\Cache\Cache;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class DoctrineCache implements StorageInterface {
    /** @var \Doctrine\Common\Cache\Cache */
    protected $client;

    public function __construct(Cache $client) {
        $this->client = $client;
    }

    public function getRateInfo($key) {
        $info = $this->client->fetch($key);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);
        $rateLimitInfo->setBlocked(isset($info['blocked']) && $info['blocked']);
        $rateLimitInfo->setKey($key);

        return $rateLimitInfo;
    }

    public function limitRate($key) {
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
        $info            = array();
        $info['limit']   = $limit;
        $info['calls']   = 1;
        $info['reset']   = time() + $period;
        $info['blocked'] = 0;

        $this->client->save($key, $info, $period);

        return $this->getRateInfo($key);
    }

    public function resetRate($key) {
        $this->client->delete($key);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setBlock(RateLimitInfo $rateLimitInfo, $periodBlock)
    {
        $resetTimestamp = time() + $periodBlock;
        $this->client->save(
            $rateLimitInfo->getKey(),
            array(
                'limit'   => $rateLimitInfo->getLimit(),
                'calls'   => $rateLimitInfo->getCalls(),
                'reset'   => $resetTimestamp,
                'blocked' => 1,
            ),
            $periodBlock
        );

        $rateLimitInfo->setBlocked(true);
        $rateLimitInfo->setResetTimestamp($resetTimestamp);

        return true;
    }
}
