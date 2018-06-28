<?php

namespace Tests\Events;

use Noxlogic\RateLimitBundle\Events\BlockEvent;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BlockEventTest extends TestCase
{
    public function testConstruct()
    {
        $rateLimitInfo = $this->createMock(RateLimitInfo::class);
        $request = $this->createMock(Request::class);
        $event = new BlockEvent($rateLimitInfo, $request);

        self::assertSame($rateLimitInfo, $event->getRateLimitInfo());
        self::assertSame($request, $event->getRequest());
    }
}
