<?php

namespace Noxlogic\RateLimitBundle\Tests\Events;

use Noxlogic\RateLimitBundle\Events\GetResponseEvent;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class GetResponseEventTest extends TestCase
{
    /**
     * @return GetResponseEvent
     */
    public function testConstruct()
    {
        $request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        $rateLimitInfo = $this->createMock('Noxlogic\RateLimitBundle\Service\RateLimitInfo');

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
        $response = $this->createMock('Symfony\Component\HttpFoundation\Response');
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
