<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class RateLimitInfoTest extends TestCase
{
    public function testRateInfoSetters()
    {
        $rateInfo = new RateLimitInfo();

        $rateInfo->setLimit(1234);
        $this->assertEquals(1234, $rateInfo->getLimit());

        $rateInfo->setCalls(5);
        $this->assertEquals(5, $rateInfo->getCalls());

        $rateInfo->setResetTimestamp(100000);
        $this->assertEquals(100000, $rateInfo->getResetTimestamp());
    }

    public function testRemainingAttempts()
    {
        $rateInfo = new RateLimitInfo();

        $rateInfo->setLimit(10);
        $rateInfo->setCalls(9);
        $this->assertEquals(1, $rateInfo->getRemainingAttempts());

        $rateInfo->setLimit(10);
        $rateInfo->setCalls(10);
        $this->assertEquals(0, $rateInfo->getRemainingAttempts());

        $rateInfo->setLimit(10);
        $rateInfo->setCalls(20);
        $this->assertEquals(0, $rateInfo->getRemainingAttempts());
    }

    public function testIsExceededLimit()
    {
        $rateInfo = new RateLimitInfo();

        $rateInfo->setLimit(10);
        $rateInfo->setCalls(9);
        $this->assertFalse($rateInfo->isExceeded());

        $rateInfo->setLimit(10);
        $rateInfo->setCalls(10);
        $this->assertFalse($rateInfo->isExceeded());

        $rateInfo->setLimit(10);
        $rateInfo->setCalls(11);
        $this->assertTrue($rateInfo->isExceeded());

        $rateInfo->setLimit(10);
        $rateInfo->setCalls(20);
        $this->assertTrue($rateInfo->isExceeded());
    }
}
