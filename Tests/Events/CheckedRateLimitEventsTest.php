<?php

namespace Noxlogic\RateLimitBundle\Tests\Events;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Events\CheckedRateLimitEvent;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CheckedRateLimitEventsTest extends TestCase
{

    public function testConstruction()
    {
        $request = new Request();
        $event = new CheckedRateLimitEvent($request, null);

        $this->assertEquals(null, $event->getRateLimit());
    }

    public function testRequest()
    {
        $request = new Request();
        $event = new CheckedRateLimitEvent($request, null);

        $this->assertEquals($request, $event->getRequest());
    }

    public function testSetRateLimit()
    {
        $request = new Request();
        $rateLimit = new RateLimit([]);

        $event = new CheckedRateLimitEvent($request, $rateLimit);

        $this->assertEquals($rateLimit, $event->getRateLimit());

        $event->setRateLimit($rateLimit);
        $this->assertEquals($rateLimit, $event->getRateLimit());
    }
}
