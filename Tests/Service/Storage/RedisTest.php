<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\GetRateInfoRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\LimitRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Exception\Storage\ResetRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\Storage\Redis;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Predis\Client;

class RedisTest extends TestCase
{
    public function testgetRateInfo()
    {
        $client = $this->getMockBuilder('Predis\\Client')
            ->setMethods(array('hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('hgetall')
              ->with('foo')
              ->will($this->returnValue(array('limit' => 100, 'calls' => 50, 'reset' => 1234)));

        $storage = new Redis($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testGetRateInfo_exception()
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(['hgetall'])
            ->getMock();
        $client->expects($this->once())
            ->method('hgetall')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new Redis($client);

        $this->expectException(GetRateInfoRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to get rate limit info: Storage error');

        $storage->getRateInfo('foo');
    }

    public function testCreateRate(): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(array('hset', 'expire', 'hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('expire')
              ->with('foo', 123);
        $client->expects($this->exactly(3))
              ->method('hset')
              ->withConsecutive(
                    array('foo', 'limit', 100),
                    array('foo', 'calls', 1),
                    array('foo', 'reset')
              );

        $storage = new Redis($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey_exception()
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(array('hset', 'expire', 'hgetall'))
            ->getMock();
        $client->expects($this->once())
            ->method('hset')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new Redis($client);

        $this->expectException(CreateRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to create rate limit: Storage error');

        $storage->createRate('foo', 100, 123);
    }

    public function testLimitRateNoKey(): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(['hgetall'])
            ->getMock();
        $client->expects($this->once())
              ->method('hgetall')
              ->with('foo')
              ->will($this->returnValue([]));

        $storage = new Redis($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRate_exception(): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(['hgetall'])
            ->getMock();
        $client->expects($this->once())
            ->method('hgetall')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new Redis($client);

        $this->expectException(LimitRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to apply rate limit: Storage error');

        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRateWithKey(): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(array('hexists', 'hincrby', 'hgetall'))
            ->getMock();
        $client->expects($this->once())
            ->method('hgetall')
            ->with('foo')
            ->willReturn([
                'limit' => 1,
                'calls' => 1,
                'reset' => 1,
            ]);
        $client->expects($this->once())
            ->method('hincrby')
            ->with('foo', 'calls', 1)
            ->willReturn(2);

        $storage = new Redis($client);
        $storage->limitRate('foo');
    }



    public function testresetRate()
    {
        $client = $this->getMockBuilder('Predis\\Client')
            ->setMethods(array('del'))
            ->getMock();
        $client->expects($this->once())
              ->method('del')
              ->with('foo');

        $storage = new Redis($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

    public function testResetRate_exception(): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(['del'])
            ->getMock();
        $client->expects($this->once())
            ->method('del')
            ->with('foo')
            ->willThrowException(new \Exception('Storage error'));

        $storage = new Redis($client);

        $this->expectException(ResetRateRateLimitStorageException::class);
        $this->expectExceptionMessage('Rate limit storage: Failed to reset rate: Storage error');

        $storage->resetRate('foo');
    }

    public function testSanitizeKey(): void
    {
        $client = $this->getMockBuilder(Client::class)
            ->addMethods(array('del'))
            ->getMock();
        $client->expects($this->once())
              ->method('del')
              ->with('PUT.POST.api_foo.2800_xxx_yyyy_zzzz_d1__41_zz_zz_x_xx_yyyy');

        $storage = new Redis($client);
        $this->assertTrue($storage->resetRate('PUT.POST.api_foo.2800:xxx:yyyy:zzzz:d1@@41:zz{zz:x}xx:yyyy'));
    }

}
