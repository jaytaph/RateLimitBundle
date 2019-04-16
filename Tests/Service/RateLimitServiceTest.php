<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Tests\EventListener\MockStorage;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class RateLimitServiceTest extends TestCase
{

    public function testSetStorage()
    {
        $mockStorage = $this->getMockBuilder('Noxlogic\\RateLimitBundle\\Service\\Storage\\StorageInterface')->getMock();

        $service = new RateLimitService();
        $service->setStorage($mockStorage);

        $this->assertEquals($mockStorage, $service->getStorage());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRuntimeExceptionWhenNoStorageIsSet()
    {
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

    public function testNoRateLimitInStorage()
    {
        $rateLimitService = new RateLimitService();
        $rateLimitService->setStorage(new MockStorage());

        $rateLimit = new RateLimit(array('methods' => 'POST', 'limit' => 1234, 'period' => 1000));

        $rateLimitInfo = $rateLimitService->getRateLimitInfo('api', $rateLimit);

        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rateLimitInfo);
        $this->assertEquals(1, $rateLimitInfo->getCalls());
        $this->assertEquals(1234, $rateLimitInfo->getLimit());
        $this->assertLessThanOrEqual(time() + 1000, $rateLimitInfo->getResetTimestamp());
    }

    public function testRateLimitInfoExistsInStorage()
    {
        $rateLimitService = new RateLimitService();
        $mockStorage = new MockStorage();
        $storageRateLimitInfo = $mockStorage->createMockRate('api', 1234, 1000, 800);
        $rateLimitService->setStorage($mockStorage);

        $rateLimit = new RateLimit(array('methods' => 'POST', 'limit' => 1234, 'period' => 1000));

        $rateLimitInfo = $rateLimitService->getRateLimitInfo('api', $rateLimit);

        $storageRateLimitInfo->setCalls(801);
        $this->assertEquals($storageRateLimitInfo, $rateLimitInfo);
    }

    public function testRateLimitInfoResetCauseGreater()
    {
        $rateLimitService = new RateLimitService();
        $mockStorage = new MockStorage();
        $storageRateLimitInfo = $mockStorage->createMockRate('api', 1234, 1000, 800, time() - 1);
        $rateLimitService->setStorage($mockStorage);

        $rateLimit = new RateLimit(array('methods' => 'POST', 'limit' => 1234, 'period' => 1000));

        $rateLimitInfo = $rateLimitService->getRateLimitInfo('api', $rateLimit);

        $this->assertNotEquals($storageRateLimitInfo, $rateLimitInfo);

        //New rateLimitInfo created
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rateLimitInfo);
        $this->assertEquals(1, $rateLimitInfo->getCalls());
        $this->assertEquals(1234, $rateLimitInfo->getLimit());
    }
}
