<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\Storage\PhpRedis;
use Noxlogic\RateLimitBundle\Service\Storage\Redis;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class PhpRedisTest extends TestCase
{
    public function setUp(): void {
        if (! class_exists('\Redis')) {
            $this->markTestSkipped('Php Redis client not installed');
        }
    }
    
    protected function getRedisMock() {
        return $this->getMockBuilder('\Redis');
    }

    protected function getStorage($client) {
        return new PhpRedis($client);
    }

    public function testgetRateInfo()
    {
        $client = $this->getRedisMock()
            ->setMethods(array('hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('hgetall')
              ->with('foo')
              ->will($this->returnValue(array('limit' => 100, 'calls' => 50, 'reset' => 1234)));

        $storage = $this->getStorage($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testcreateRate()
    {
        $client = $this->getRedisMock()
            ->setMethods(array('hset', 'expire', 'hgetall'))
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

        $storage = $this->getStorage($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey()
    {
        $client = $this->getRedisMock()
            ->setMethods(array('hgetall'))
            ->getMock();
        $client->expects($this->once())
              ->method('hgetall')
              ->with('foo')
              ->will($this->returnValue([]));

        $storage = $this->getStorage($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRateWithKey()
    {
        $client = $this->getRedisMock()
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

        $storage = $this->getStorage($client);
        $storage->limitRate('foo');
    }



    public function testresetRate()
    {
        $client = $this->getRedisMock()
            ->setMethods(array('del'))
            ->getMock();
        $client->expects($this->once())
              ->method('del')
              ->with('foo');

        $storage = $this->getStorage($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

}
