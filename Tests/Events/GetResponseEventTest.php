<?php

namespace Noxlogic\RateLimitBundle\Tests\Events;

use Noxlogic\RateLimitBundle\Events\GetResponseEvent;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetResponseEventTest extends TestCase
{
    /**
     * @return GetResponseEvent
     */
    public function testConstruct()
    {
        $request = $this->createMock(Request::class);
        $rateLimitInfo = $this->createMock(RateLimitInfo::class);

        $event = new GetResponseEvent($request, $rateLimitInfo);

        self::assertSame($request, $event->getRequest());
        self::assertSame($rateLimitInfo, $event->getRateLimitInfo());

        return $event;
    }

    /**
     * @depends testConstruct
     *
     * @param GetResponseEvent $event
     */
    public function testHasResponseReturnFalse(GetResponseEvent $event)
    {
        self::assertFalse($event->hasResponse());
    }

    /**
     * @depends testConstruct
     *
     * @param GetResponseEvent $event
     * @return GetResponseEvent
     */
    public function testSetResponse(GetResponseEvent $event)
    {
        $response = $this->createMock(Response::class);
        $event->setResponse($response);
        self::assertSame($response, $event->getResponse());

        return $event;
    }

    /**
     * @depends testSetResponse
     *
     * @param GetResponseEvent $event
     */
    public function testHasResponseReturnTrue(GetResponseEvent $event)
    {
        self::assertTrue($event->hasResponse());
    }
}
