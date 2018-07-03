<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\PsrCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class PsrCacheTest extends TestCase
{
    public function testGetRateInfo()
    {
        $item = $this->getMockBuilder('Psr\\Cache\\CacheItemInterface')
            ->getMock();
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $item->expects($this->once())
            ->method('get')
            ->willReturn(array('limit' => 100, 'calls' => 50, 'reset' => 1234));

        $client = $this->getMockBuilder('Psr\\Cache\\CacheItemPoolInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->will($this->returnValue($item));

        $storage = new PsrCache($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testCreateRate()
    {
        $item = $this->getMockBuilder('Psr\\Cache\\CacheItemInterface')
            ->getMock();
        $item->expects($this->once())
            ->method('set')
            ->willReturn(true);
        $item->expects($this->once())
            ->method('expiresAfter')
            ->willReturn(true);

        $client = $this->getMockBuilder('Psr\\Cache\\CacheItemPoolInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->will($this->returnValue($item));
        $client->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn(true);

        $storage = new PsrCache($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey()
    {
        $item = $this->getMockBuilder('Psr\\Cache\\CacheItemInterface')
            ->getMock();
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $client = $this->getMockBuilder('Psr\\Cache\\CacheItemPoolInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->will($this->returnValue($item));

        $storage = new PsrCache($client);
        $this->assertFalse($storage->limitRate('foo'));
    }

    public function testLimitRateWithKey()
    {
        $item = $this->getMockBuilder('Psr\\Cache\\CacheItemInterface')
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

        $client = $this->getMockBuilder('Psr\\Cache\\CacheItemPoolInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('getItem')
            ->with('foo')
            ->will($this->returnValue($item));
        $client->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn(true);

        $storage = new PsrCache($client);
        $storage->limitRate('foo');
    }

    public function testResetRate()
    {
        $client = $this->getMockBuilder('Psr\\Cache\\CacheItemPoolInterface')
            ->getMock();
        $client->expects($this->once())
            ->method('deleteItem')
            ->with('foo')
            ->willReturn(true);

        $storage = new PsrCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

    public function testSetBlock()
    {
        $client = $this->getMockBuilder('Psr\Cache\CacheItemPoolInterface')->getMock();
        $item = $this->createMock('Psr\Cache\CacheItemInterface');

        $client->expects($this->once())
               ->method('getItem')
               ->with('foo')
               ->willReturn($item);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setKey('foo');
        $rateLimitInfo->setCalls(1);
        $rateLimitInfo->setLimit(2);
        $rateLimitInfo->setResetTimestamp(time());

        $periodBlock = 100;
        $resetTimestamp = time() + $periodBlock;
        $item->expects(self::once())
             ->method('set')
             ->with([
                 'limit'   => $rateLimitInfo->getLimit(),
                 'calls'   => $rateLimitInfo->getCalls(),
                 'reset'   => $resetTimestamp,
                 'blocked' => 1
             ]);
        $item->expects(self::once())
             ->method('expiresAfter')
             ->with($periodBlock);
        $client->expects(self::once())
               ->method('save')
               ->with($item)
               ->willReturn(true);

        $storage = new PsrCache($client);
        self::assertTrue($storage->setBlock($rateLimitInfo, $periodBlock), 'Result of setting the block must equal true');
        self::assertTrue($rateLimitInfo->isBlocked(), 'After setting the block RateLimitInfo must contain blocked=true');
        self::assertEquals($resetTimestamp, $rateLimitInfo->getResetTimestamp());
    }
} 
