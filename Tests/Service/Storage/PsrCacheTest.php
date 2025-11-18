<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Composer\InstalledVersions;
use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\Storage\PsrCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;

class PsrCacheTest extends TestCase
{
    public function testGetRateInfo(): void
    {
        $item = $this->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $item->expects($this->once())
            ->method('get')
            ->willReturn(array('limit' => 100, 'calls' => 50, 'reset' => 1234));

        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn($item);

        $storage = new PsrCache($client);

        $rli = $storage->getRateInfo('foo');

        $this->assertInstanceOf(RateLimitInfo::class, $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testGetRateInfo_exception(): void
    {
        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new PsrCache($client);

        $this->expectException(GetRateInfoRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to get rate limit info: Storage error');

        $storage->getRateInfo('foo');
    }

    public function testCreateRate(): void
    {
        $item = $this->getMockBuilder(CacheItemInterface::class)
            ->getMock();

        /**
         * psr/cache 3.0 changed the return type of set() and expiresAfter() to return self.
         * @TODO NEXT_MAJOR: Remove this check and the first conditional block when psr/cache <3 support is dropped.
         */
        $psrCacheVersion = InstalledVersions::getVersion('psr/cache');
        if (version_compare($psrCacheVersion, '3.0', '<')) {
            $item->expects($this->once())
                ->method('set')
                ->willReturn(true);
            $item->expects($this->once())
                ->method('expiresAfter')
                ->willReturn(true);
        } else {
            $item->expects($this->once())
                ->method('set')
                ->willReturnSelf();
            $item->expects($this->once())
                ->method('expiresAfter')
                ->willReturnSelf();
        }

        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn($item);
        $client->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn(true);

        $storage = new PsrCache($client);
        $storage->createRate('foo', 100, 123);
    }

    public function testCreateRate_exception(): void
    {
        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new PsrCache($client);

        $this->expectException(CreateRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to create rate limit: Storage error');

        $storage->createRate('foo', 100, 123);
    }

    public function testLimitRateNoKey(): void
    {
        $item = $this->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn($item);

        $storage = new PsrCache($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRate_exception(): void
    {
        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new PsrCache($client);

        $this->expectException(LimitRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to apply rate limit: Storage error');

        $storage->limitRate('foo');
    }

    public function testLimitRateWithKey(): void
    {
        $item = $this->getMockBuilder(CacheItemInterface::class)
            ->getMock();
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $item->expects($this->once())
            ->method('get')
            ->willReturn(array('limit' => 100, 'calls' => 50, 'reset' => 1234));
        $item->expects($this->once())
            ->method('set');
        $item->expects($this->once())
            ->method('expiresAfter');

        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->willReturn($item);
        $client->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn(true);

        $storage = new PsrCache($client);
        $storage->limitRate('foo');
    }

    public function testResetRate(): void
    {
        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('deleteItem')
            ->with('foo')
            ->willReturn(true);

        $storage = new PsrCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

    public function testResetRate_exception(): void
    {
        $client = $this->getMockBuilder(CacheItemPoolInterface::class)
            ->getMock();
        $client->expects($this->once())
            ->method('deleteItem')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new PsrCache($client);

        $this->expectException(ResetRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to reset rate: Storage error');

        $storage->resetRate('foo');
    }
}
