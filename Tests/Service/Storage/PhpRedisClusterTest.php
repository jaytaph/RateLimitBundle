<?php


namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;


use Noxlogic\RateLimitBundle\Service\Storage\PhpRedisCluster;

class PhpRedisClusterTest extends PhpRedisTest
{
    public function setUp(): void {
        if (! class_exists('\RedisCluster')) {
            $this->markTestSkipped('Php Redis client not installed');
        }
    }

    protected function getRedisMock() {
        return $this->getMockBuilder('\RedisCluster')->disableOriginalConstructor();
    }

    protected function getStorage($client) {
        return new PhpRedisCluster($client);
    }
}