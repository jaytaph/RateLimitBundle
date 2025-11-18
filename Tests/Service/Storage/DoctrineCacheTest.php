<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;


use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Doctrine\Common\Cache\Cache;

class DoctrineCacheTest extends TestCase
{
    public function testGetRateInfo(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willReturn(['limit' => 100, 'calls' => 50, 'reset' => 1234]);

        $storage = new DoctrineCache($client);

        $rli = $storage->getRateInfo('foo');

        $this->assertInstanceOf(RateLimitInfo::class, $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testGetRateInfo_exception(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('save');

        $storage = new DoctrineCache($client);
        $storage->createRate('foo', 100, 123);
    }

    public function testCreateRate_exception(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willReturn(false);

        $storage = new DoctrineCache($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRateWithKey(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();

        $info['limit'] = 100;
        $info['calls'] = 50;
        $info['reset'] = 1234;

        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willReturn($info);
        $client->expects($this->once())
            ->method('save');

        $storage = new DoctrineCache($client);
        $storage->limitRate('foo');
    }

    public function testLimitRate_exception(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo');

        $storage = new DoctrineCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }
} 
