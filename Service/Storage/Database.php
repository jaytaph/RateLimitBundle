<?php

namespace Noxlogic\RateLimitBundle\Service\Storage;


use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Handlers\PdoHandler;

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
            $this->client->writeToDB();
            return null;
        }

        $info = json_decode($info, true);
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setLimit($info['limit']);
        $rateLimitInfo->setCalls($info['calls']);
        $rateLimitInfo->setResetTimestamp($info['reset']);

        return $rateLimitInfo;
    }

    public function limitRate($key) {
        $info = $this->client->fetch($key);
        if (!$info) {
            $this->client->writeToDB();

            return false;
        }

        $info = json_decode($info, true);
        $info['calls']++;
        $this->client->save($key, json_encode($info));

        return $this->getRateInfo($key);
    }

    public function createRate($key, $limit, $period) {
        $info = [];
        $info['limit'] = $limit;
        $info['calls'] = 1;
        $info['reset'] = time() + $period;

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
