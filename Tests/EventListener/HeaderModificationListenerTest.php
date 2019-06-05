<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\EventListener\HeaderModificationListener;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class HeaderModificationListenerTest extends TestCase
{

    public function testListenerWithoutInfo()
    {
        $event = $this->createEvent();

        $listener = new HeaderModificationListener();
        $listener->setParameter('display_headers', true);
        $listener->setParameter('header_limit_name', 'X-RateLimit-Limit');
        $listener->setParameter('header_remaining_name', 'X-RateLimit-Remaining');
        $listener->setParameter('header_reset_name', 'X-RateLimit-Reset');
        $listener->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Limit'));
        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Reset'));
        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Remaining'));
    }


    public function testListenerWithInfo()
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setCalls(5);
        $rateLimitInfo->setLimit(10);
        $rateLimitInfo->setResetTimestamp(1520000);

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('rate_limit_info', $rateLimitInfo);

        $listener = new HeaderModificationListener();
        $listener->setParameter('display_headers', true);
        $listener->setParameter('header_limit_name', 'X-RateLimit-Limit');
        $listener->setParameter('header_remaining_name', 'X-RateLimit-Remaining');
        $listener->setParameter('header_reset_name', 'X-RateLimit-Reset');
        $listener->onKernelResponse($event);

        $this->assertEquals(10, $event->getResponse()->headers->has('X-RateLimit-Limit'));
        $this->assertEquals(5, $event->getResponse()->headers->has('X-RateLimit-Remaining'));
        $this->assertEquals(1520000, $event->getResponse()->headers->has('X-RateLimit-Reset'));
    }

    public function testListenerWithDisplayHeaderFalse()
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setCalls(5);
        $rateLimitInfo->setLimit(10);
        $rateLimitInfo->setResetTimestamp(1520000);

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('rate_limit_info', $rateLimitInfo);

        $listener = new HeaderModificationListener();
        $listener->setParameter('display_headers', false);
        $listener->setParameter('header_limit_name', 'X-RateLimit-Limit');
        $listener->setParameter('header_remaining_name', 'X-RateLimit-Remaining');
        $listener->setParameter('header_reset_name', 'X-RateLimit-Reset');
        $listener->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Limit'));
        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Reset'));
        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Remaining'));
    }

    public function testListenerWithCustomHeaders()
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setCalls(5);
        $rateLimitInfo->setLimit(10);
        $rateLimitInfo->setResetTimestamp(1520000);

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('rate_limit_info', $rateLimitInfo);

        $listener = new HeaderModificationListener();
        $listener->setParameter('display_headers', true);
        $listener->setParameter('header_limit_name', 'foo');
        $listener->setParameter('header_remaining_name', 'bar');
        $listener->setParameter('header_reset_name', 'baz');
        $listener->onKernelResponse($event);

        $this->assertTrue($event->getResponse()->headers->has('foo'));
        $this->assertTrue($event->getResponse()->headers->has('bar'));
        $this->assertTrue($event->getResponse()->headers->has('baz'));
    }

    public function testListenerRemainingCannotBeNegative()
    {
        $rateLimitInfo = new RateLimitInfo();
        $rateLimitInfo->setCalls(500);
        $rateLimitInfo->setLimit(10);
        $rateLimitInfo->setResetTimestamp(1520000);

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('rate_limit_info', $rateLimitInfo);

        $listener = new HeaderModificationListener();
        $listener->setParameter('display_headers', true);
        $listener->setParameter('header_limit_name', 'X-RateLimit-Limit');
        $listener->setParameter('header_remaining_name', 'X-RateLimit-Remaining');
        $listener->setParameter('header_reset_name', 'X-RateLimit-Reset');
        $listener->onKernelResponse($event);

        $this->assertEquals(0, $event->getResponse()->headers->get('X-RateLimit-Remaining'));
    }

    public function testListenerWithoutRateInfo()
    {
        $event = $this->createEvent();

        $listener = new HeaderModificationListener();
        $listener->setParameter('display_headers', true);
        $listener->setParameter('header_limit_name', 'X-RateLimit-Limit');
        $listener->setParameter('header_remaining_name', 'X-RateLimit-Remaining');
        $listener->setParameter('header_reset_name', 'X-RateLimit-Reset');
        $listener->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Limit'));
        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Reset'));
        $this->assertFalse($event->getResponse()->headers->has('X-RateLimit-Remaining'));
    }

    /**
     * @return ResponseEvent
     */
    protected function createEvent()
    {
        $request = new Request();
        $response = new Response();

        $event = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\Event\\ResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event
            ->method('getRequest')
            ->willReturn($request);
        $event
            ->method('getResponse')
            ->willReturn($response);

        return $event;
    }
}
