<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\SimpleCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;

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
            ->will($this->returnValue(null));

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

    public function testSetBlock()
    {
        $client = $this->getMockBuilder('Psr\SimpleCache\CacheInterface')->getMock();

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setKey('foo');
        $rateLimitInfo->setCalls(1);
        $rateLimitInfo->setLimit(2);
        $rateLimitInfo->setResetTimestamp(time());

        $periodBlock = 100;
        $resetTimestamp = time() + $periodBlock;
        $client->expects(self::once())
               ->method('set')
               ->with(
                   'foo',
                   [
                       'limit'   => $rateLimitInfo->getLimit(),
                       'calls'   => $rateLimitInfo->getCalls(),
                       'reset'   => $resetTimestamp,
                       'blocked' => 1
                   ],
                   $periodBlock
               )
               ->willReturn(true);

        $storage = new SimpleCache($client);
        self::assertTrue($storage->setBlock($rateLimitInfo, $periodBlock), 'Result of setting the block must equal true');
        self::assertTrue($rateLimitInfo->isBlocked(), 'After setting the block RateLimitInfo must contain blocked=true');
        self::assertEquals($resetTimestamp, $rateLimitInfo->getResetTimestamp());
    }
} 
