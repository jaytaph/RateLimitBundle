<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\EventListener\RateLimitAnnotationListener;
use Noxlogic\RateLimitBundle\Events\AbstractEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Tests\EventListener\MockStorage;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RateLimitAnnotationListenerTest extends TestCase
{
    static $usedDispatcher;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!class_exists('Symfony\\Contracts\\EventDispatcher\\EventDispatcherInterface')) {
            self::$usedDispatcher = 'Symfony\\Component\\EventDispatcher\\EventDispatcherInterface';
        } else {
            self::$usedDispatcher = 'Symfony\\Contracts\\EventDispatcher\\EventDispatcherInterface';
        }
    }

    /**
     * @var MockStorage
     */
    protected $mockStorage;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $mockPathLimitProcessor;

    protected function setUp(): void
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

        if (class_exists('Symfony\\Component\\HttpKernel\\Event\\ControllerEvent')) {
            $event = new ControllerEvent($kernel, function() {}, $request, HttpKernelInterface::MASTER_REQUEST);
        } else {
            $event = new FilterControllerEvent($kernel, function () {}, $request, HttpKernelInterface::MASTER_REQUEST);
        }

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
        $this->assertIsArray($event->getController());
        $listener->onKernelController($event);
        $this->assertIsArray( $event->getController());
        $listener->onKernelController($event);
        $this->assertIsArray($event->getController());
        $listener->onKernelController($event);
        $this->assertIsArray($event->getController());
        $listener->onKernelController($event);
        $this->assertIsArray($event->getController());
        $listener->onKernelController($event);
        $this->assertIsNotArray($event->getController());
        $listener->onKernelController($event);
        $this->assertIsNotArray($event->getController());
        $listener->onKernelController($event);
        $this->assertIsNotArray($event->getController());
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
        $storage->createMockRate('Noxlogic.RateLimitBundle.Tests.EventListener.MockController.mockAction', 5, 10, 6);
        $listener->onKernelController($event);
        $this->assertIsNotArray($event->getController());
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
        $storage->createMockRate('Noxlogic.RateLimitBundle.Tests.EventListener.MockController.mockAction', 5, -10, 12);
        $listener->onKernelController($event);
        $this->assertIsArray($event->getController());
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
     * @param int $requestType
     * @param Request|null $request
     * @return ControllerEvent|FilterControllerEvent
     */
    protected function createEvent($requestType = HttpKernelInterface::MASTER_REQUEST, Request $request = null)
    {
        $kernel = $this->getMockBuilder('Symfony\\Component\\HttpKernel\\HttpKernelInterface')->getMock();

        $controller = new MockController();
        $action = 'mockAction';

        $request = $request === null ? new Request() : $request;

        if (class_exists('Symfony\\Component\\HttpKernel\\Event\\ControllerEvent')) {
            return new ControllerEvent($kernel, array($controller, $action), $request, $requestType);
        }

        return new FilterControllerEvent($kernel, array($controller, $action), $request, $requestType);
    }


    protected function createListener($expects, $setStorage = true)
    {
        $mockDispatcher = $this->getMockBuilder(self::$usedDispatcher)->getMock();
        $mockDispatcher
            ->expects($expects)
            ->method('dispatch');

        $rateLimitService = new RateLimitService();
        if ($setStorage) {
            $rateLimitService->setStorage($this->getMockStorage());
        }

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
        $mockDispatcher = $this->getMockBuilder(self::$usedDispatcher)->getMock();
        $generatedCallback = function ($name, $event) use ($request, &$generated) {
            if ($name !== RateLimitEvents::GENERATE_KEY) {
                return;
            }
            $generated = true;
            $this->assertSame(RateLimitEvents::GENERATE_KEY, $name);
            $this->assertSame($request, $event->getRequest());
            $this->assertSame(['foo'], $event->getPayload());
            $this->assertSame('Noxlogic.RateLimitBundle.Tests.EventListener.MockController.mockAction', $event->getKey());
        };
        $mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function ($arg1, $arg2) use ($generatedCallback) {
                if ($arg1 instanceof AbstractEvent) {
                    $generatedCallback($arg2, $arg1);
                    return $arg1;
                } else {
                    $generatedCallback($arg1, $arg2);
                    return $arg2;
                }
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
        $storage->createMockRate('Noxlogic.RateLimitBundle.Tests.EventListener.MockController.mockAction', 5, 10, 6);

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

    public function testRateLimitThrottlingWithException()
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->expectExceptionCode(123);
        $this->expectDeprecationMessage('a message');
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
        $storage->createMockRate('Noxlogic.RateLimitBundle.Tests.EventListener.MockController.mockAction', 5, 10, 6);
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
        $storage->createMockRate('Noxlogic.RateLimitBundle.Tests.EventListener.MockController.mockAction', 5, 10, 6);

        /** @var Response $response */
        $listener->onKernelController($event);

        // Call the controller, it will return a response object
        $a = $event->getController();
        $response = $a();

        $this->assertEquals($response->getStatusCode(), 123);
        $this->assertEquals($response->getContent(), "a message");
    }

    public function testRateLimitThrottlingWithFailOpen()
    {
        $listener = $this->createListener($this->any(), false);

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 5, 'period' => 3, 'failOpen' => true)),
        ));

        $response = $listener->onKernelController($event);

        $this->assertNull($response);
    }

    public function testRateLimitThrottlingWithFailOpenFalse()
    {
        $listener = $this->createListener($this->any(), false);
        $request = new Request();

        $event = $this->createEvent(HttpKernelInterface::MASTER_REQUEST, $request);
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit(array('limit' => 5, 'period' => 3, 'failOpen' => false)),
        ));

        $listener->onKernelController($event);
        $a = $event->getController();
        $response = $a();

    }
}
