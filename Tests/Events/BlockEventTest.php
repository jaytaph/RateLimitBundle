<?php

namespace Tests\Events;

use Noxlogic\RateLimitBundle\Events\BlockEvent;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class BlockEventTest extends TestCase
{
    public function testConstruct()
    {
        $rateLimitInfo = $this->getMockBuilder('Noxlogic\RateLimitBundle\Service\RateLimitInfo')->getMock();
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $event = new BlockEvent($rateLimitInfo, $request);

        self::assertSame($rateLimitInfo, $event->getRateLimitInfo());
        self::assertSame($request, $event->getRequest());
    }
}
