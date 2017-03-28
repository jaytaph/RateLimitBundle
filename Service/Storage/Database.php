<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;


use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Entity\PdoHandler;

class Database implements StorageInterface
{
    //define client
    /**
     * @var PdoHandler
     */
    protected $client;

    public function __construct(PdoHandler $client) {
        $this->client = $client;
    }

    public function getRateInfo($key) {
        $info = $this->client->fetch($key);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

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
        $info          = array();
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

        $this->client->save($key, $info);

        return $this->getRateInfo($key);
    }

    public function resetRate($key) {
        $this->client->delete($key);
        return true;
    }

    public function fetch($key){
        return $this->client->fetch($key);
    }
}