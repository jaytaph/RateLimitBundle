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
        if (!$info) {
            return null;
        }

        $info = json_decode($info, true);
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['time']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['lifetime']);

        return $rateLimitInfo;
    }

    public function limitRate($key) {
        $info = $this->client->fetch($key);
        if (!is_array($info) || !array_key_exists('time', $info)) {
            return false;
        }

        $info['calls']++;

        $expire = $info['lifetime'] - time();

        $this->client->save($key, $info, $expire);

        return $this->getRateInfo($key);
    }

    public function createRate($key, $limit, $period) {
        $info = [];
        $info['time'] = $limit;
        $info['calls'] = 1;
        $info['lifetime'] = time() + $period;

        $this->client->save($key, json_encode($info));

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
