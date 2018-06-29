<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;


use Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;

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

    public function testCreateRate()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\Cache')
            ->getMock();
        $client->expects($this->once())
            ->method('save');

        $storage = new DoctrineCache($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\Cache')
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



    public function testResetRate()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\Cache')
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo');

        $storage = new DoctrineCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }
} 
