<?php

namespace Tests\Events;

use Noxlogic\RateLimitBundle\Events\BlockEvent;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class BlockEventTest extends TestCase
{
    public function testConstruct()
    {
        $rateLimitInfo = $this->createMock('Noxlogic\RateLimitBundle\Service\RateLimitInfo');
        $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $event = new BlockEvent($rateLimitInfo, $request);

        self::assertSame($rateLimitInfo, $event->getRateLimitInfo());
        self::assertSame($request, $event->getRequest());
    }
}
