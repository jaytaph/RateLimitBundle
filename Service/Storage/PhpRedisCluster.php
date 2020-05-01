<?php


namespace Noxlogic\RateLimitBundle\Service\Storage;


class PhpRedisCluster extends PhpRedis
{

    public function __construct(\RedisCluster $client)
    {
        $this->client = $client;
    }

}