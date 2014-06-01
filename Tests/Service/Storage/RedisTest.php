<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\Storage\Memcache;
use Noxlogic\RateLimitBundle\Service\Storage\Redis;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class RedisTest extends TestCase
{

    function setUp() {
        if (! class_exists('Predis\\Client')) {
            $this->markTestSkipped('Predis client not installed');
        }
    }

    public function testTest()
    {
        $this->assertTrue(true);
    }

//    /**
//     * @expectedException \RuntimeException
//     */
//    public function testSetStorage()
//    {
//        $storage = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\Storage\\Redis');
//        $this->assertTrue($storage);
//    }
//
//    /**
//     * @expectedException \RuntimeException
//     */
//    public function testGetRateInfo()
//    {
//        $storage = new Redis();
//        $this->assertTrue($storage->getRateInfo('testkey'));
//    }
//
//    /**
//     * @expectedException \RuntimeException
//     */
//    public function testLimitRate()
//    {
//        $storage = new Redis();
//        $this->assertTrue($storage->limitRate('testkey'));
//    }
//
//    /**
//     * @expectedException \RuntimeException
//     */
//    public function testCreateRate()
//    {
//        $storage = new Redis();
//        $this->assertTrue($storage->createRate('testkey', 10, 100));
//    }
//
//    /**
//     * @expectedException \RuntimeException
//     */
//    public function testResetRate()
//    {
//        $storage = new Redis();
//        $this->assertTrue($storage->resetRate('testkey'));
//    }
}
