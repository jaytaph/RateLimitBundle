<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Service\RateLimitInfoManager;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Tests\EventListener\MockStorage;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class RateLimitInfoManagerTest extends TestCase
{
    public function testNoRateLimitInStorage()
    {
        $rateLimitService = new RateLimitService();
        $rateLimitService->setStorage(new MockStorage());

        $rateLimitInfoManager = new RateLimitInfoManager($rateLimitService);

        $rateLimit = new RateLimit(array('methods' => 'POST', 'limit' => 1234, 'period' => 1000));

        $rateLimitInfo = $rateLimitInfoManager->getRateLimitInfo('api', $rateLimit);

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

        $rateLimitInfoManager = new RateLimitInfoManager($rateLimitService);
        $rateLimit = new RateLimit(array('methods' => 'POST', 'limit' => 1234, 'period' => 1000));

        $rateLimitInfo = $rateLimitInfoManager->getRateLimitInfo('api', $rateLimit);

        $storageRateLimitInfo->setCalls(801);
        $this->assertEquals($storageRateLimitInfo, $rateLimitInfo);
    }

    public function testRateLimitInfoResetCauseGreater()
    {
        $rateLimitService = new RateLimitService();
        $mockStorage = new MockStorage();
        $storageRateLimitInfo = $mockStorage->createMockRate('api', 1234, 1000, 800, time() - 1);
        $rateLimitService->setStorage($mockStorage);

        $rateLimitInfoManager = new RateLimitInfoManager($rateLimitService);

        $rateLimit = new RateLimit(array('methods' => 'POST', 'limit' => 1234, 'period' => 1000));

        $rateLimitInfo = $rateLimitInfoManager->getRateLimitInfo('api', $rateLimit);

        $this->assertNotEquals($storageRateLimitInfo, $rateLimitInfo);

        //New rateLimitInfo created
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\Service\\RateLimitInfo', $rateLimitInfo);
        $this->assertEquals(1, $rateLimitInfo->getCalls());
        $this->assertEquals(1234, $rateLimitInfo->getLimit());
    }
}
