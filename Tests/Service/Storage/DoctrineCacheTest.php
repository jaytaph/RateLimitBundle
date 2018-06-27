<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;


use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class DoctrineCacheTest extends TestCase
{
    function setUp() {
        if (! class_exists('Doctrine\\Common\\Cache\\ArrayCache')) {
            $this->markTestSkipped('Doctrine cache not installed');
        }
    }

    public function testgetRateInfo()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\ArrayCache')
            ->setMethods(array('fetch'))
            ->getMock();
        $client->expects($this->once())
            ->method('fetch')
            ->with('foo')
            ->will($this->returnValue(array('limit' => 100, 'calls' => 50, 'reset' => 1234, 'blocked' => 1)));

        $storage = new DoctrineCache($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
        $this->assertTrue($rli->isBlocked());
    }

    public function testcreateRate()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\ArrayCache')
            ->setMethods(array('save'))
            ->getMock();
        $client->expects($this->once())
            ->method('save');

        $storage = new DoctrineCache($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\ArrayCache')
            ->setMethods(array('fetch'))
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
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\ArrayCache')
            ->setMethods(array('fetch', 'save'))
            ->getMock();

        $info['limit'] = 100;
        $info['calls'] = 50;
        $info['reset'] = 1234;

        $client->expects($this->exactly(2))
            ->method('fetch')
            ->with('foo')
            ->will($this->returnValue($info));
        $client->expects($this->once())
            ->method('save');

        $storage = new DoctrineCache($client);
        $storage->limitRate('foo');
    }



    public function testresetRate()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\ArrayCache')
            ->setMethods(array('delete'))
            ->getMock();
        $client->expects($this->once())
            ->method('delete')
            ->with('foo');

        $storage = new DoctrineCache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

    public function testSetBlock()
    {
        $client = $this->getMockBuilder('Doctrine\\Common\\Cache\\ArrayCache')
                       ->setMethods(array('save'))
                       ->getMock();
        $client->expects(self::once())
               ->method('save')
               ->with('foo', ['limit' => 5, 'calls' => 6, 'reset' => time() + 100, 'blocked' => 1,], 100)
               ->willReturn(true);

        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setKey('foo');
        $rateLimitInfo->setResetTimestamp(10);
        $rateLimitInfo->setLimit(5);
        $rateLimitInfo->setCalls(6);

        $storage = new DoctrineCache($client);
        self::assertTrue($storage->setBlock($rateLimitInfo, 100));
        self::assertTrue($rateLimitInfo->isBlocked());
        self::assertGreaterThan(100, $rateLimitInfo->getResetTimestamp());
    }
} 
