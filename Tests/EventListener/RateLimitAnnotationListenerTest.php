<?php

namespace Noxlogic\RateLimitBundle\EventListener\Tests;

use Noxlogic\RateLimitBundle\Annotation\XRateLimit;
use Noxlogic\RateLimitBundle\EventListener\RateLimitAnnotationListener;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MockController {
    function mockAction() { }
}


class RateLimitAnnotationListenerTest extends TestCase
{

    function testReturnedWhenNotAMasterRequest()
    {
        $mockDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $mockDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $reader = $this->getMock('Doctrine\\Common\\Annotations\\Reader');
        $rateLimitService = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\RateLimitService');

        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);

        $listener = new RateLimitAnnotationListener($reader, $mockDispatcher, $rateLimitService);
        $listener->onKernelController($event);

        $this->assertTrue(true);
    }


    function testReturnedWhenNoControllerFound()
    {
        $mockDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $mockDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $reader = $this->getMock('Doctrine\\Common\\Annotations\\Reader');
        $rateLimitService = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\RateLimitService');

        $listener = new RateLimitAnnotationListener($reader, $mockDispatcher, $rateLimitService);


        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');
        $request = new Request();
        $event = new FilterControllerEvent($kernel, function() {}, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener->onKernelController($event);
    }


    function testReturnedWhenNoAnnotationsFound()
    {
        $mockDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $mockDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $reader = $this->getMock('Doctrine\\Common\\Annotations\\Reader');
        $rateLimitService = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\RateLimitService');

        $event = $this->createEvent();

        $listener = new RateLimitAnnotationListener($reader, $mockDispatcher, $rateLimitService);
        $listener->onKernelController($event);
    }


    function testDispatchIsCalled()
    {
        $mockDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $mockDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(RateLimitEvents::GENERATE_KEY);

        $reader = $this->getMock('Doctrine\\Common\\Annotations\\Reader');
        $rateLimitService = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\RateLimitService');

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new XRateLimit(array('limit' => 100, 'period' => 3600)),
        ));

        $listener = new RateLimitAnnotationListener($reader, $mockDispatcher, $rateLimitService);
        $listener->onKernelController($event);

        $this->assertTrue(true);
    }


    function testBestMethodMatch()
    {
        $mockDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $mockDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $reader = $this->getMock('Doctrine\\Common\\Annotations\\Reader');
        $rateLimitService = $this->getMock('Noxlogic\\RateLimitBundle\\Service\\RateLimitService');

        $listener = new RateLimitAnnotationListener($reader, $mockDispatcher, $rateLimitService);
        $method = new ReflectionMethod(
            get_class($listener), 'findBestMethodMatch'
        );
        $method->setAccessible(true);

        $request = new Request();

//        $this->assertEquals(
//            $annotations[1], $method->invoke($listener, $request, $annotations)
//        );
    }

    /**
     * @return FilterControllerEvent
     */
    protected function createEvent($type = HttpKernelInterface::MASTER_REQUEST)
    {
        $kernel = $this->getMock('Symfony\\Component\\HttpKernel\\HttpKernelInterface');

        $controller = new MockController();
        $action = 'mockAction';

        $request = new Request();
        $event = new FilterControllerEvent($kernel, array($controller, $action), $request, $type);
        return $event;
    }
}
