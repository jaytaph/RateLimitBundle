<?php

namespace Noxlogic\RateLimitBundle\Tests\Service;

use Noxlogic\RateLimitBundle\EventListener\OauthKeyGenerateListener;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RateLimitServiceTest extends TestCase
{

    public function testSetStorage()
    {
        $mockStorage = $this->getMockBuilder('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface')->getMock();

        $service = new RateLimitService();
        $service->setStorage($mockStorage);

        $this->assertEquals($mockStorage, $service->getStorage());
    }

    public function testRuntimeExceptionWhenNoStorageIsSet()
    {
        $this->expectException(\RuntimeException::class);
        $service = new RateLimitService();
        $service->getStorage();
    }


    public function testLimitRate()
    {
        $mockStorage = $this->getMockBuilder('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface')->getMock();
        $mockStorage
            ->expects($this->once())
            ->method('limitRate')
            ->with('testkey');

        $service = new RateLimitService();
        $service->setStorage($mockStorage);
        $service->limitRate('testkey');
    }

    public function testcreateRate()
    {
        $mockStorage = $this->getMockBuilder('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface')->getMock();
        $mockStorage
            ->expects($this->once())
            ->method('createRate')
            ->with('testkey', 10, 100);

        $service = new RateLimitService();
        $service->setStorage($mockStorage);
        $service->createRate('testkey', 10, 100);
    }

    public function testResetRate()
    {
        $mockStorage = $this->getMockBuilder('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface')->getMock();
        $mockStorage
            ->expects($this->once())
            ->method('resetRate')
            ->with('testkey');

        $service = new RateLimitService();
        $service->setStorage($mockStorage);
        $service->resetRate('testkey');
    }
}
