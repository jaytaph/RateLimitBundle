<?php

namespace Noxlogic\RateLimitBundle\Tests\Service\Storage;

use Noxlogic\RateLimitBundle\Service\Storage\Memcache;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class MemcacheTest extends TestCase
{
    public function setUp(): void
    {
        if (!class_exists('\\MemCached')) {
            $this->markTestSkipped('MemCached extension not installed');
        }
    }

    public function testGetRateInfo()
    {
        $client = @$this->getMockBuilder('\\Memcached')
            ->setMethods(array('get'))
            ->getMock();
        $client->expects($this->once())
              ->method('get')
              ->with('foo')
              ->will($this->returnValue(array('limit' => 100, 'calls' => 50, 'reset' => 1234)));

        $storage = new Memcache($client);
        $rli = $storage->getRateInfo('foo');
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rli);
        $this->assertEquals(100, $rli->getLimit());
        $this->assertEquals(50, $rli->getCalls());
        $this->assertEquals(1234, $rli->getResetTimestamp());
    }

    public function testCreateRate()
    {
        $client = @$this->getMockBuilder('\\Memcached')
            ->setMethods(array('set', 'get'))
            ->getMock();
        $client->expects($this->exactly(1))
              ->method('set');

        $storage = new Memcache($client);
        $storage->createRate('foo', 100, 123);
    }


    public function testLimitRateNoKey()
    {
        $client = @$this->getMockBuilder('\\Memcached')
            ->setMethods(array('get','getResultCode'))
            ->getMock();
        $client->expects($this->any())
                ->method('getResultCode')
                ->willReturn(\Memcached::RES_SUCCESS);
        $client->expects($this->atLeastOnce())
              ->method('get')
              ->with('foo')
              ->will($this->returnValue(array('limit' => 100, 'calls' => 1, 'reset' => 1234)));

        $storage = new Memcache($client);
        $storage->limitRate('foo');
    }

    public function testLimitRateWithKey()
    {
        $client = @$this->getMockBuilder('\\Memcached')
            ->setMethods(array('get','cas','getResultCode'))
            ->getMock();
        $client->expects($this->any())
                ->method('getResultCode')
                ->willReturn(\Memcached::RES_SUCCESS);
        $client->expects($this->exactly(1))
              ->method('get')
              ->with('foo')
              ->willReturn(false);

        $storage = new Memcache($client);
        $storage->limitRate('foo');
    }

    public function testResetRate()
    {
        $client = @$this->getMockBuilder('\\Memcached')
            ->setMethods(array('delete'))
            ->getMock();
        $client->expects($this->once())
              ->method('delete')
              ->with('foo');

        $storage = new Memcache($client);
        $this->assertTrue($storage->resetRate('foo'));
    }

}
