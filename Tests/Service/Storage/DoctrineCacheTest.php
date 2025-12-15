<?php
declare(strict_types=1);

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Doctrine\Common\Cache\Cache;

class DoctrineCacheTest extends TestCase
{
    public function testGetRateInfo()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\Cache')
            ->getMock();
        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->will($this->returnValue(array('limit' => 100, 'calls' => 50, 'reset' => 1234)));

        $storage = new DoctrineCache($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testGetRateInfo_exception()
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new DoctrineCache($client);

        $this->expectException(GetRateInfoRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to get rate limit info: Storage error');

        $storage->getRateInfo('foo');
    }

    public function testCreateRate(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('save');

        $storage = new DoctrineCache($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testCreateRate_exception()
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new DoctrineCache($client);

        $this->expectException(CreateRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to create rate limit: Storage error');

        $storage->createRate('foo', 100, 123);
    }

    public function testLimitRateNoKey(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->will($this->returnValue(false));

        $storage = new DoctrineCache($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRateWithKey()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\Cache')
            ->getMock();

        $info['limit'] = 100;
        $info['calls'] = 50;
        $info['reset'] = 1234;

        $client->expects($this->exactly(1))
            ->method('fetch')
            ->with('foo')
            ->will($this->returnValue($info));
        $client->expects($this->once())
            ->method('save');

        $storage = new DoctrineCache($client);
        $storage->limitRate('foo');
    }



    public function testLimitRate_exception()
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new DoctrineCache($client);

        $this->expectException(LimitRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to apply rate limit: Storage error');

        $storage->limitRate('foo');
    }

    public function testResetRate(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo');

        $storage = new DoctrineCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

    public function testResetRate_exception(): void
    {
        $client = $this->getMockBuilder(Cache::class)
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new DoctrineCache($client);

        $this->expectException(ResetRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to reset rate: Storage error');

        $storage->resetRate('foo');
    }
} 
