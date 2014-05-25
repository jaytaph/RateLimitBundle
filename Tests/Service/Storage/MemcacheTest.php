<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\Storage\Memcache;
use Noxlogic\RateLimitBundle\Tests\TestCase;


class MemcacheTest extends TestCase
{

    /**
     * @expectedException \RuntimeException
     */
    public function testSetStorage()
    {
        $storage = new Memcache();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetRateInfo()
    {
        $storage = new Memcache();
        $storage->getRateInfo('testkey');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testLimitRate()
    {
        $storage = new Memcache();
        $storage->limitRate('testkey');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateRate()
    {
        $storage = new Memcache();
        $storage->createRate('testkey', 10, 100);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testResetRate()
    {
        $storage = new Memcache();
        $storage->resetRate('testkey');
    }

}
