<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\PhpRedis;
use Noxlogic\RateLimitBundle\Service\Storage\Redis;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class PhpRedisTest extends TestCase
{
    public function setUp() {
        if (! class_exists('\Redis')) {
            $this->markTestSkipped('Php Redis client not installed');
        }
    }

    public function testgetRateInfo()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('hgetall')
              ->with('foo')
              ->will($this->returnValue(array('limit' => 100, 'calls' => 50, 'reset' => 1234, 'blocked' => 1)));

        $storage = new PhpRedis($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
        $this->assertTrue($rli->isBlocked());
    }

    public function testcreateRate()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('hset', 'expire', 'hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('expire')
              ->with('foo', 123);
        $client->expects($this->exactly(4))
              ->method('hset')
              ->withConsecutive(
                    array('foo', 'limit', 100),
                    array('foo', 'calls', 1),
                    array('foo', 'reset'),
                    array('foo', 'blocked', 0)
              );

        $storage = new PhpRedis($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('hgetall')
              ->with('foo')
              ->will($this->returnValue([]));

        $storage = new PhpRedis($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRateWithKey()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('hincrby', 'hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('hgetall')
              ->with('foo')
              ->will($this->returnValue([
                  'limit' => 1,
                  'calls' => 1,
                  'reset' => 1,
              ]));
        $client->expects($this->once())
              ->method('hincrby')
              ->with('foo', 'calls', 1)
              ->will($this->returnValue(2));

        $storage = new PhpRedis($client);
        $storage->limitRate('foo');
    }



    public function testresetRate()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('del'))
            ->getMock();
        $client->expects($this->once())
              ->method('del')
              ->with('foo');

        $storage = new PhpRedis($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

    public function testSetBlock()
    {
        $client = $this->getMockBuilder('\Redis')
                       ->setMethods(array('hset', 'expire'))
                       ->getMock();
        $client->expects(self::exactly(4))
               ->method('hset')
               ->withConsecutive(
                   array('foo', 'limit', 2),
                   array('foo', 'calls', 1),
                   array('foo', 'reset', time() + 100),
                   array('foo', 'blocked', 1)
               );
        $client->expects(self::once())
               ->method('expire')
               ->with('foo', 100);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setKey('foo');
        $rateLimitInfo->setResetTimestamp(10);
        $rateLimitInfo->setLimit(2);
        $rateLimitInfo->setCalls(1);

        $storage = new PhpRedis($client);
        self::assertTrue($storage->setBlock($rateLimitInfo, 100));
        self::assertTrue($rateLimitInfo->isBlocked());
        self::assertGreaterThan(10, $rateLimitInfo->getResetTimestamp());
    }
}
