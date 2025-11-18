<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\Storage\SimpleCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Psr\SimpleCache\CacheInterface;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class SimpleCacheTest extends TestCase
{
    public function testGetRateInfo(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(['limit' => 100, 'calls' => 50, 'reset' => 1234]);

        $storage = new SimpleCache($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf(RateLimitInfo::class, $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testCreateRate()
    {
        $client = $this->getMockBuilder('Psr\\SimpleCache\\CacheInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('set');

        $storage = new SimpleCache($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey()
    {
        $client = $this->getMockBuilder('Psr\\SimpleCache\\CacheInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn(null);

        $storage = new SimpleCache($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRateWithKey()
    {
        $client = $this->getMockBuilder('Psr\\SimpleCache\\CacheInterface')
            ->getMock();

        $info['limit'] = 100;
        $info['calls'] = 50;
        $info['reset'] = 1234;

        $client->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($info);
        $client->expects($this->once())
            ->method('set');

        $storage = new SimpleCache($client);
        $storage->limitRate('foo');
    }

    public function testResetRate(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo');

        $storage = new SimpleCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }
} 
