<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\Storage\SimpleCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Psr\SimpleCache\CacheInterface;

class SimpleCacheTest extends TestCase
{
    public function testGetRateInfo()
    {
        $client = $this->getMockBuilder('Psr\\SimpleCache\\CacheInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue(array('limit' => 100, 'calls' => 50, 'reset' => 1234)));

        $storage = new SimpleCache($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testGetRateInfo_exception(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new SimpleCache($client);

        $this->expectException(GetRateInfoRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to get rate limit info: Storage error');

        $storage->getRateInfo('foo');
    }

    public function testCreateRate(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('set');

        $storage = new SimpleCache($client);
        $storage->createRate('foo', 100, 123);
    }

    public function testCreateRate_exception(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('set')
            ->willThrowException(new \Exception('Storage error'));;

        $storage = new SimpleCache($client);

        $this->expectException(CreateRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to create rate limit: Storage error');

        $storage->createRate('foo', 100, 123);
    }

    public function testLimitRateNoKey(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue(null));

        $storage = new SimpleCache($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRate_exception(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new SimpleCache($client);

        $this->expectException(LimitRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to apply rate limit: Storage error');

        $storage->limitRate('foo');
    }

    public function testLimitRateWithKey(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();

        $info['limit'] = 100;
        $info['calls'] = 50;
        $info['reset'] = 1234;

        $client->expects($this->exactly(1))
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($info));
        $client->expects($this->once())
            ->method('set');

        $storage = new SimpleCache($client);
        $storage->limitRate('foo');
    }

    public function testResetRate()
    {
        $client = $this->getMockBuilder('Psr\\SimpleCache\\CacheInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo');

        $storage = new SimpleCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

    public function testResetRate_exception(): void
    {
        $client = $this->getMockBuilder(CacheInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new SimpleCache($client);

        $this->expectException(ResetRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to reset rate: Storage error');

        $storage->resetRate('foo');
    }
}
