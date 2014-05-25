<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

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
        $mockStorage = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface');

        $service = new RateLimitService();
        $service->setStorage($mockStorage);

        $this->assertEquals($mockStorage, $service->getStorage());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRuntimeExceptionWhenNoStorageIsSet()
    {
        $service = new RateLimitService();
        $service->getStorage();
    }


    public function testLimitRate()
    {
        $mockStorage = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface');
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
        $mockStorage = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface');
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
        $mockStorage = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface');
        $mockStorage
            ->expects($this->once())
            ->method('resetRate')
            ->with('testkey');

        $service = new RateLimitService();
        $service->setStorage($mockStorage);
        $service->resetRate('testkey');
    }


}
