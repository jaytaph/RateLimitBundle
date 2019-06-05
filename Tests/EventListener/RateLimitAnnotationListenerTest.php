<?php

namespace Noxlogic\RateLimitBundle\EventListener\Tests;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\EventListener\RateLimitAnnotationListener;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Tests\EventListener\MockStorage;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MockController {
    function mockAction() { }
}


class RateLimitAnnotationListenerTest extends TestCase
{

    /**
     * @var MockStorage
     */
    protected $mockStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $mockPathLimitProcessor;

    protected function setUp()
    {
        $this->mockStorage = new MockStorage();
        $this->mockPathLimitProcessor = $this->getMockBuilder('Noxlogic\RateLimitBundle\Util\PathLimitProcessor')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getMockStorage()
    {
        return $this->mockStorage;
    }


    public function testReturnedWhenNotEnabled()
    {
        $listener = $this->createListener($this->never());
        $listener->setParameter('enabled', false);

        $event = $this->createEvent();

        $listener->onKernelController($event);
    }


    public function testReturnedWhenNotAMasterRequest()
    {
        $listener = $this->createListener($this->never());

        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);
        $listener->onKernelController($event);
    }


    public function testReturnedWhenNoControllerFound()
    {
        $listener = $this->createListener($this->once());

        $kernel = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\HttpKernelInterface')->getMock();
        $request = new Request();
        $event = new ControllerEvent($kernel, function() {}, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener->onKernelController($event);
    }


    public function testReturnedWhenNoAnnotationsFound()
    {
        $listener = $this->createListener($this->once());

        $event = $this->createEvent();
        $listener->onKernelController($event);
    }

    public function testDelegatesToPathLimitProcessorWhenNoAnnotationsFound()
    {
        $request = new Request();
        $event = $this->createEvent(HttpKernelInterface::MASTER_REQUEST, $request);

        $listener = $this->createListener($this->once());

        $this->mockPathLimitProcessor->expects($this->once())
                                     ->method('getRateLimit')
                                     ->with($request);

        $listener->onKernelController($event);
    }

    public function testDispatchIsCalled()
    {
        $listener = $this->createListener($this->exactly(2));

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 100, 'period' => 3600)),
        ));

        $listener->onKernelController($event);
    }

    public function testDispatchIsCalledIfThePathLimitProcessorReturnsARateLimit()
    {
        $event = $this->createEvent(HttpKernelInterface::MASTER_REQUEST);

        $listener = $this->createListener($this->exactly(2));

        $rateLimit = new RateLimit(array(
            'limit' => 100,
            'period' => 200
        ));

        $this->mockPathLimitProcessor->expects($this->any())
                                     ->method('getRateLimit')
                                     ->will($this->returnValue($rateLimit));

        $listener->onKernelController($event);
    }

    public function testIsRateLimitSetInRequest()
    {
        $listener = $this->createListener($this->any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 5, 'period' => 10)),
        ));


        $this->assertNull($event->getRequest()->attributes->get('rate_limit_info'));

        // Create initial ratelimit in storage
        $listener->onKernelController($event);
        $this->assertArrayHasKey('rate_limit_info', $event->getRequest()->attributes->all());

        // Add second ratelimit in storage
        $listener->onKernelController($event);
        $this->assertArrayHasKey('rate_limit_info', $event->getRequest()->attributes->all());
    }

    public function testRateLimit()
    {
        $listener = $this->createListener($this->any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 5, 'period' => 5)),
        ));

        $listener->onKernelController($event);
        $this->assertInternalType('array', $event->getController());
        $listener->onKernelController($event);
        $this->assertInternalType('array', $event->getController());
        $listener->onKernelController($event);
        $this->assertInternalType('array', $event->getController());
        $listener->onKernelController($event);
        $this->assertInternalType('array', $event->getController());
        $listener->onKernelController($event);
        $this->assertInternalType('array', $event->getController());
        $listener->onKernelController($event);
        $this->assertNotInternalType('array', $event->getController());
        $listener->onKernelController($event);
        $this->assertNotInternalType('array', $event->getController());
        $listener->onKernelController($event);
        $this->assertNotInternalType('array', $event->getController());
    }

    public function testRateLimitThrottling()
    {
        $listener = $this->createListener($this->any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
                new RateLimit(array('limit' => 5, 'period' => 3)),
        ));

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate('Noxlogic.RateLimitBundle.EventListener.Tests.MockController.mockAction', 5, 10, 6);
        $listener->onKernelController($event);
        $this->assertNotInternalType('array', $event->getController());
    }

    public function testRateLimitExpiring()
    {
        $listener = $this->createListener($this->any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 5, 'period' => 3)),
        ));

        // Expired
        $storage = $this->getMockStorage();
        $storage->createMockRate('Noxlogic.RateLimitBundle.EventListener.Tests.MockController.mockAction', 5, -10, 12);
        $listener->onKernelController($event);
        $this->assertInternalType('array', $event->getController());
    }

    public function testBestMethodMatch()
    {
        $listener = $this->createListener($this->any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array(
            new RateLimit(array('limit' => 100, 'period' => 3600)),
            new RateLimit(array('methods' => 'GET', 'limit' => 100, 'period' => 3600)),
            new RateLimit(array('methods' => array('POST', 'PUT'), 'limit' => 100, 'period' => 3600)),
        );

        // Find the method that matches the string
        $request->setMethod('GET');
        $this->assertEquals(
            $annotations[1],
            $method->invoke($listener, $request, $annotations)
        );

        // Method not found, use the default one
        $request->setMethod('DELETE');
        $this->assertEquals(
            $annotations[0],
            $method->invoke($listener, $request, $annotations)
        );

        // Find best match based in methods in array
        $request->setMethod('PUT');
        $this->assertEquals(
            $annotations[2],
            $method->invoke($listener, $request, $annotations)
        );
    }


    public function testFindNoAnnotations()
    {
        $listener = $this->createListener($this->any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array();

        $request->setMethod('PUT');
        $this->assertNull($method->invoke($listener, $request, $annotations));

        $request->setMethod('GET');
        $this->assertNull($method->invoke($listener, $request, $annotations));
    }


    public function testFindBestMethodMatchNotMatchingAnnotations()
    {
        $listener = $this->createListener($this->any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array(
            new RateLimit(array('methods' => 'GET', 'limit' => 100, 'period' => 3600)),
        );

        $request->setMethod('PUT');
        $this->assertNull($method->invoke($listener, $request, $annotations));

        $request->setMethod('GET');
        $this->assertEquals(
            $annotations[0],
            $method->invoke($listener, $request, $annotations)
        );
    }


    public function testFindBestMethodMatchMatchingMultipleAnnotations()
    {
        $listener = $this->createListener($this->any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array(
            new RateLimit(array('methods' => 'GET', 'limit' => 100, 'period' => 3600)),
            new RateLimit(array('methods' => array('GET','PUT'), 'limit' => 200, 'period' => 7200)),
        );

        $request->setMethod('PUT');
        $this->assertEquals($annotations[1], $method->invoke($listener, $request, $annotations));

        $request->setMethod('GET');
        $this->assertEquals($annotations[1], $method->invoke($listener, $request, $annotations));
    }

    /**
     * @return ControllerEvent
     */
    protected function createEvent($type = HttpKernelInterface::MASTER_REQUEST, Request $request = null)
    {
        $kernel = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\HttpKernelInterface')->getMock();

        $controller = new MockController();
        $action = 'mockAction';

        $request = $request === null ? new Request() : $request;
        $event = new ControllerEvent($kernel, array($controller, $action), $request, $type);
        return $event;
    }


    protected function createListener($expects)
    {
        $mockDispatcher = $this->getMockBuilder('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface')->getMock();
        $mockDispatcher
            ->expects($expects)
            ->method('dispatch');

        $rateLimitService = new RateLimitService();
        $rateLimitService->setStorage($this->getMockStorage());

        return new RateLimitAnnotationListener(
            $mockDispatcher,
            $rateLimitService,
            $this->mockPathLimitProcessor
        );
    }

    public function testRateLimitKeyGenerationEventHasPayload()
    {
        $event = $this->createEvent();
        $request = $event->getRequest();
        $request->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 5, 'period' => 3, 'payload' => ['foo'])),
        ));

        $generated = false;
        $mockDispatcher = $this->getMockBuilder('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface')->getMock();
        $mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function ($name, $event) use ($request, &$generated) {
                if ($name !== RateLimitEvents::GENERATE_KEY) {
                    return;
                }
                $generated = true;
                $this->assertSame(RateLimitEvents::GENERATE_KEY, $name);
                $this->assertSame($request, $event->getRequest());
                $this->assertSame(['foo'], $event->getPayload());
                $this->assertSame('Noxlogic.RateLimitBundle.EventListener.Tests.MockController.mockAction', $event->getKey());
            });

        $storage = $this->getMockStorage();
        $storage->createMockRate('test-key', 5, 10, 1);

        $rateLimitService = $this->getMockBuilder('Noxlogic\RateLimitBundle\Service\RateLimitService')
            ->getMock();

        $listener = new RateLimitAnnotationListener($mockDispatcher, $rateLimitService, $this->mockPathLimitProcessor);
        $listener->onKernelController($event);

        $this->assertTrue($generated, 'Generate key event not dispatched');
    }

    public function testRateLimitThrottlingWithExceptionAndPayload()
    {
        $listener = $this->createListener($this->any());
        $listener->setParameter('rate_response_exception', 'Noxlogic\RateLimitBundle\Tests\Exception\TestException');
        $listener->setParameter('rate_response_code', 123);
        $listener->setParameter('rate_response_message', 'a message');

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 5, 'period' => 3, 'payload' => ['foo'])),
        ));

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate('Noxlogic.RateLimitBundle.EventListener.Tests.MockController.mockAction', 5, 10, 6);

        try {
            $listener->onKernelController($event);

            $this->assertFalse(true, 'Exception not being thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Noxlogic\RateLimitBundle\Tests\Exception\TestException', $e);
            $this->assertSame(123, $e->getCode());
            $this->assertSame('a message', $e->getMessage());
            $this->assertSame(['foo'], $e->payload);
        }
    }

    /**
     * @expectedException \BadFunctionCallException
     * @expectedExceptionCode 123
     * @expectedExceptionMessage a message
     */
    public function testRateLimitThrottlingWithException()
    {
        $listener = $this->createListener($this->any());
        $listener->setParameter('rate_response_exception', '\BadFunctionCallException');
        $listener->setParameter('rate_response_code', 123);
        $listener->setParameter('rate_response_message', 'a message');

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
                new RateLimit(array('limit' => 5, 'period' => 3)),
        ));

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate('Noxlogic.RateLimitBundle.EventListener.Tests.MockController.mockAction', 5, 10, 6);
        $listener->onKernelController($event);
    }

    public function testRateLimitThrottlingWithMessages()
    {
        $listener = $this->createListener($this->any());
        $listener->setParameter('rate_response_code', 123);
        $listener->setParameter('rate_response_message', 'a message');

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
                new RateLimit(array('limit' => 5, 'period' => 3)),
        ));

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate('Noxlogic.RateLimitBundle.EventListener.Tests.MockController.mockAction', 5, 10, 6);

        /** @var Response $response */
        $listener->onKernelController($event);

        // Call the controller, it will return a response object
        $a = $event->getController();
        $response = $a();

        $this->assertEquals($response->getStatusCode(), 123);
        $this->assertEquals($response->getContent(), "a message");
    }
}
