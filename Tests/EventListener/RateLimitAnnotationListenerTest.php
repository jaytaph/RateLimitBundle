<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

use Noxlogic\RateLimitBundle\Attribute\RateLimit;
use Noxlogic\RateLimitBundle\EventListener\RateLimitAnnotationListener;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Exception\Storage\CreateRateRateLimitStorageException;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Noxlogic\RateLimitBundle\Tests\Exception\TestException;

class RateLimitAnnotationListenerTest extends TestCase
{
    protected MockStorage $mockStorage;

    protected MockObject $mockPathLimitProcessor;

    protected function setUp(): void
    {
        $this->mockStorage = new MockStorage();
        $this->mockPathLimitProcessor = $this->getMockBuilder(PathLimitProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        $this->mockStorage->resetAll();
    }

    protected function getMockStorage(): MockStorage
    {
        return $this->mockStorage;
    }


    public function testReturnedWhenNotEnabled(): void
    {
        $listener = $this->createListener(self::never());
        $listener->setParameter('enabled', false);

        $event = $this->createEvent();
        $listener->onKernelController($event);
    }


    public function testReturnedWhenNotAMasterRequest(): void
    {
        $listener = $this->createListener(self::never());

        $event = $this->createEvent(HttpKernelInterface::SUB_REQUEST);
        $listener->onKernelController($event);
    }


    public function testReturnedWhenNoControllerFound(): void
    {
        $listener = $this->createListener(self::once());

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $request = new Request();

        $event = new ControllerEvent($kernel, static function() {}, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener->onKernelController($event);
    }


    public function testReturnedWhenNoAttributesFound(): void
    {
        $listener = $this->createListener(self::once());

        $event = $this->createEvent();
        $listener->onKernelController($event);
    }

    public function testDelegatesToPathLimitProcessorWhenNoAttributesFound(): void
    {
        $request = new Request();
        $event = $this->createEvent(HttpKernelInterface::MAIN_REQUEST, $request);

        $listener = $this->createListener(self::once());

        $this->mockPathLimitProcessor->expects(self::once())
                                     ->method('getRateLimit')
                                     ->with($request);

        $listener->onKernelController($event);
    }

    public function testDispatchIsCalled(): void
    {
        $listener = $this->createListener(self::exactly(2));

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit([], 100, 3600),
        ));

        $listener->onKernelController($event);
    }

    public function testDispatchIsCalledWithAttributes(): void
    {
        $listener = $this->createListener(self::exactly(2));

        $event = $this->createEvent(
            HttpKernelInterface::MAIN_REQUEST,
            null,
            new MockControllerWithAttributes()
        );

        $listener->onKernelController($event);
    }

    public function testDispatchIsCalledIfThePathLimitProcessorReturnsARateLimit(): void
    {
        $event = $this->createEvent(HttpKernelInterface::MAIN_REQUEST);

        $listener = $this->createListener(self::exactly(2));
        $rateLimit = new RateLimit(
            [],
            100,
            200
        );

        $this->mockPathLimitProcessor
            ->method('getRateLimit')
            ->willReturn($rateLimit);

        $listener->onKernelController($event);
    }

    public function testIsRateLimitSetInRequest(): void
    {
        $listener = $this->createListener(self::any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit([], 5, 10),
        ));


        self::assertNull($event->getRequest()->attributes->get('rate_limit_info'));

        // Create initial ratelimit in storage
        $listener->onKernelController($event);
        self::assertArrayHasKey('rate_limit_info', $event->getRequest()->attributes->all());

        // Add second ratelimit in storage
        $listener->onKernelController($event);
        self::assertArrayHasKey('rate_limit_info', $event->getRequest()->attributes->all());
    }

    public function testRateLimit(): void
    {
        $listener = $this->createListener(self::any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit([], 5, 5),
        ));

        $listener->onKernelController($event);
        self::assertIsArray($event->getController());
        $listener->onKernelController($event);
        self::assertIsArray( $event->getController());
        $listener->onKernelController($event);
        self::assertIsArray($event->getController());
        $listener->onKernelController($event);
        self::assertIsArray($event->getController());
        $listener->onKernelController($event);
        self::assertIsArray($event->getController());
        $listener->onKernelController($event);
        self::assertIsNotArray($event->getController());
        $listener->onKernelController($event);
        self::assertIsNotArray($event->getController());
        $listener->onKernelController($event);
        self::assertIsNotArray($event->getController());
    }

    public function testRateLimitThrottling(): void
    {
        $listener = $this->createListener(self::any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', [
            new RateLimit([], 5, 3),
        ]);

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate(MockController::RATE_LIMIT_KEY, 5, 10, 6);
        $listener->onKernelController($event);

        self::assertIsNotArray($event->getController());
    }

    public function testRateLimitExpiring(): void
    {
        $listener = $this->createListener(self::any());

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit([], 5, 3),
        ));

        // Expired
        $storage = $this->getMockStorage();
        $storage->createMockRate(MockController::RATE_LIMIT_KEY, 5, -10, 12);
        $listener->onKernelController($event);
        self::assertIsArray($event->getController());
    }

    public function testBestMethodMatch(): void
    {
        $listener = $this->createListener(self::any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array(
            new RateLimit([], 100,  3600),
            new RateLimit('GET', 100,  3600),
            new RateLimit(['POST', 'PUT'], 100,  3600),
        );

        // Find the method that matches the string
        $request->setMethod('GET');
        self::assertSame(
            $annotations[1],
            $method->invoke($listener, $request, $annotations)
        );

        // Method not found, use the default one
        $request->setMethod('DELETE');
        self::assertSame(
            $annotations[0],
            $method->invoke($listener, $request, $annotations)
        );

        // Find best match based in methods in array
        $request->setMethod('PUT');
        self::assertSame(
            $annotations[2],
            $method->invoke($listener, $request, $annotations)
        );
    }

    public function testFindNoAttributes(): void
    {
        $listener = $this->createListener(self::any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array();

        $request->setMethod('PUT');
        self::assertNull($method->invoke($listener, $request, $annotations));

        $request->setMethod('GET');
        self::assertNull($method->invoke($listener, $request, $annotations));
    }

    public function testFindBestMethodMatchNotMatchingAnnotations(): void
    {
        $listener = $this->createListener(self::any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array(
            new RateLimit('GET', 100, 3600),
        );

        $request->setMethod('PUT');
        self::assertNull($method->invoke($listener, $request, $annotations));

        $request->setMethod('GET');
        self::assertSame(
            $annotations[0],
            $method->invoke($listener, $request, $annotations)
        );
    }


    public function testFindBestMethodMatchMatchingMultipleAnnotations(): void
    {
        $listener = $this->createListener(self::any());
        $method = new ReflectionMethod(get_class($listener), 'findBestMethodMatch');
        $method->setAccessible(true);

        $request = new Request();

        $annotations = array(
            new RateLimit('GET', 100, 3600),
            new RateLimit(['GET','PUT'], 200, 7200),
        );

        $request->setMethod('PUT');
        self::assertSame($annotations[1], $method->invoke($listener, $request, $annotations));

        $request->setMethod('GET');
        self::assertSame($annotations[1], $method->invoke($listener, $request, $annotations));
    }

    private function createEvent(
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
        ?Request $request = null,
        ?MockController $controller = null,
        string $controllerAction = 'mockAction'
    ): ControllerEvent
    {
        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $controller = $controller ?? new MockController();

        $request = $request ?? new Request();

        return new ControllerEvent($kernel, [$controller, $controllerAction], $request, $requestType);
    }

    private function createListener($eventDispatcherExpects): RateLimitAnnotationListener
    {
        $mockDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $mockDispatcher
            ->expects($eventDispatcherExpects)
            ->method('dispatch');

        $rateLimitService = new RateLimitService();
        $rateLimitService->setStorage($this->getMockStorage());

        return new RateLimitAnnotationListener(
            $mockDispatcher,
            $rateLimitService,
            $this->mockPathLimitProcessor
        );
    }

    public function testRateLimitKeyGenerationEventHasPayload(): void
    {
        $event = $this->createEvent();
        $request = $event->getRequest();
        $request->attributes->set('_x-rate-limit', array(
            new RateLimit([], 5, 3, ['foo']),
        ));

        $generated = false;
        $mockDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $generatedCallback = function ($name, $event) use ($request, &$generated) {
            if ($name !== RateLimitEvents::GENERATE_KEY) {
                return;
            }
            $generated = true;
            self::assertSame(RateLimitEvents::GENERATE_KEY, $name);
            self::assertSame($request, $event->getRequest());
            self::assertSame(['foo'], $event->getPayload());
            self::assertSame(MockController::RATE_LIMIT_KEY, $event->getKey());
        };
        $mockDispatcher
            ->expects(self::any())
            ->method('dispatch')
            ->willReturnCallback(function ($arg1, $arg2) use ($generatedCallback) {
                if ($arg1 instanceof Event) {
                    $generatedCallback($arg2, $arg1);
                    return $arg1;
                } else {
                    $generatedCallback($arg1, $arg2);
                    return $arg2;
                }
            });

        $storage = $this->getMockStorage();
        $storage->createMockRate('test-key', 5, 10, 1);

        $rateLimitService = $this->getMockBuilder(RateLimitService::class)
            ->getMock();

        $listener = new RateLimitAnnotationListener($mockDispatcher, $rateLimitService, $this->mockPathLimitProcessor);
        $listener->onKernelController($event);

        self::assertTrue($generated, 'Generate key event not dispatched');
    }

    public function testRateLimitThrottlingWithExceptionAndPayload(): void
    {
        $listener = $this->createListener(self::any());
        $listener->setParameter('rate_response_exception', TestException::class);
        $listener->setParameter('rate_response_code', 123);
        $listener->setParameter('rate_response_message', 'a message');

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
            new RateLimit([], 5, 3, ['foo']),
        ));

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate(MockController::RATE_LIMIT_KEY, 5, 10, 6);

        try {
            $listener->onKernelController($event);

            self::fail('Exception not being thrown');
        } catch (\Exception $e) {
            self::assertInstanceOf(TestException::class, $e);
            self::assertSame(123, $e->getCode());
            self::assertSame('a message', $e->getMessage());
            self::assertSame(['foo'], $e->payload);
        }
    }

    public function testRateLimitThrottling_failOpenFalse_exceptionShouldHappenOnStorageError(): void
    {
        $listener = $this->createListener(self::any());
        $listener->setParameter('rate_response_exception', TestException::class);
        $listener->setParameter('fail_open', false);

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', [
            new RateLimit([], 5, 3),
        ]);

        // Throttled
        $this->getMockStorage()->createStorageErrorMockRate(
            MockController::RATE_LIMIT_KEY,
            new CreateRateRateLimitStorageException(new \Exception('A storage error happened'))
        );

        $this->expectException(CreateRateRateLimitStorageException::class);

        $listener->onKernelController($event);
    }

    public function testRateLimitThrottling_failOpenTrueViaConfig_noExceptionShouldHappenOnStorageError(): void
    {
        $listener = $this->createListener(self::any());
        $listener->setParameter('rate_response_exception', TestException::class);
        $listener->setParameter('fail_open', true);

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', [
            new RateLimit([], 5, 3),
        ]);

        // Throttled
        $this->getMockStorage()->createStorageErrorMockRate(
            MockController::RATE_LIMIT_KEY,
            new CreateRateRateLimitStorageException(new \Exception('A storage error happened'))
        );

        $listener->onKernelController($event);

        $this->expectNotToPerformAssertions();
    }

    public function testRateLimitThrottling_failOpenTrueViaAttribute_noExceptionShouldHappenOnStorageError(): void
    {
        $listener = $this->createListener(self::any());
        $listener->setParameter('rate_response_exception', TestException::class);
        // Fail open is configured to be disabled globally, but the attribute overrides it
        $listener->setParameter('fail_open', false);

        $event = $this->createEvent(
            controller: new MockControllerWithAttributes(),
            controllerAction: 'failOpenMockAction'
        );

        // Throttled
        $this->getMockStorage()->createStorageErrorMockRate(
            MockController::RATE_LIMIT_KEY,
            new CreateRateRateLimitStorageException(new \Exception('A storage error happened'))
        );

        $listener->onKernelController($event);

        $this->expectNotToPerformAssertions();
    }

    public function testRateLimitThrottling_failOpenFalseViaAttribute_noExceptionShouldHappenOnStorageError(): void
    {
        $listener = $this->createListener(self::any());
        $listener->setParameter('rate_response_exception', TestException::class);
        // Fail open is configured to be enabled globally, but the attribute overrides it
        $listener->setParameter('fail_open', true);

        $event = $this->createEvent(
            controller: new MockControllerWithAttributes(),
            controllerAction: 'doNotFailOpenMockAction'
        );

        // Throttled
        $this->getMockStorage()->createStorageErrorMockRate(
            MockController::RATE_LIMIT_KEY,
            new CreateRateRateLimitStorageException(new \Exception('A storage error happened'))
        );

        $listener->onKernelController($event);

        $this->expectNotToPerformAssertions();
    }

    public function testRateLimitThrottlingWithException(): void
    {
        $this->expectException(\BadFunctionCallException::class);
        $this->expectExceptionCode(123);
        $this->expectExceptionMessage('a message');

        $listener = $this->createListener(self::any());
        $listener->setParameter('rate_response_exception', \BadFunctionCallException::class);
        $listener->setParameter('rate_response_code', 123);
        $listener->setParameter('rate_response_message', 'a message');

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
                new RateLimit([], 5, 3),
        ));

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate(MockController::RATE_LIMIT_KEY, 5, 10, 6);
        $listener->onKernelController($event);
    }

    public function testRateLimitThrottlingWithMessages(): void
    {
        $listener = $this->createListener(self::any());
        $listener->setParameter('rate_response_code', 123);
        $listener->setParameter('rate_response_message', 'a message');

        $event = $this->createEvent();
        $event->getRequest()->attributes->set('_x-rate-limit', array(
                new RateLimit([], 5, 3),
        ));

        // Throttled
        $storage = $this->getMockStorage();
        $storage->createMockRate(MockController::RATE_LIMIT_KEY, 5, 10, 6);

        /** @var Response $response */
        $listener->onKernelController($event);

        // Call the controller, it will return a response object
        $a = $event->getController();
        $response = $a();

        self::assertSame(123, $response->getStatusCode());
        self::assertSame("a message", $response->getContent());
    }
}
