<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Events\CheckedRateLimitEvent;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Exception\RateLimitExceptionInterface;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as LegacyEventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RateLimitAnnotationListener extends BaseListener
{

    /**
     * @var EventDispatcherInterface | LegacyEventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Noxlogic\RateLimitBundle\Service\RateLimitService
     */
    protected $rateLimitService;

    /**
     * @var \Noxlogic\RateLimitBundle\Util\PathLimitProcessor
     */
    protected $pathLimitProcessor;

    /**
     * @param RateLimitService                    $rateLimitService
     */
    public function __construct(
        $eventDispatcher,
        RateLimitService $rateLimitService,
        PathLimitProcessor $pathLimitProcessor
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->rateLimitService = $rateLimitService;
        $this->pathLimitProcessor = $pathLimitProcessor;
    }

    /**
     * @param ControllerEvent|FilterControllerEvent $event
     */
    public function onKernelController($event)
    {
        // Skip if the bundle isn't enabled (for instance in test environment)
        if( ! $this->getParameter('enabled', true)) {
            return;
        }

        // Skip if we aren't the main request
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        // Find the best match
        $annotations = $event->getRequest()->attributes->get('_x-rate-limit', array());
        $rateLimit = $this->findBestMethodMatch($event->getRequest(), $annotations);

        // Another treatment before applying RateLimit ?
        $checkedRateLimitEvent = new CheckedRateLimitEvent($event->getRequest(), $rateLimit);
        $this->dispatch(RateLimitEvents::CHECKED_RATE_LIMIT, $checkedRateLimitEvent);
        $rateLimit = $checkedRateLimitEvent->getRateLimit();

        // No matching annotation found
        if (! $rateLimit) {
            return;
        }

        $key = $this->getKey($event, $rateLimit, $annotations);

        // Ratelimit the call
        $rateLimitInfo = $this->rateLimitService->limitRate($key);
        if (! $rateLimitInfo) {
            // Create new rate limit entry for this call
            $rateLimitInfo = $this->rateLimitService->createRate($key, $rateLimit->getLimit(), $rateLimit->getPeriod());
            if (! $rateLimitInfo) {
                // @codeCoverageIgnoreStart
                return;
                // @codeCoverageIgnoreEnd
            }
        }


        // Store the current rating info in the request attributes
        $request = $event->getRequest();
        $request->attributes->set('rate_limit_info', $rateLimitInfo);

        // Reset the rate limits
        if(time() >= $rateLimitInfo->getResetTimestamp()) {
            $this->rateLimitService->resetRate($key);
            $rateLimitInfo = $this->rateLimitService->createRate($key, $rateLimit->getLimit(), $rateLimit->getPeriod());
            if (! $rateLimitInfo) {
                // @codeCoverageIgnoreStart
                return;
                // @codeCoverageIgnoreEnd
            }
        }

        // When we exceeded our limit, return a custom error response
        if ($rateLimitInfo->getCalls() > $rateLimitInfo->getLimit()) {

            // Throw an exception if configured.
            if ($this->getParameter('rate_response_exception')) {
                $class = $this->getParameter('rate_response_exception');

                $e = new $class($this->getParameter('rate_response_message'), $this->getParameter('rate_response_code'));

                if ($e instanceof RateLimitExceptionInterface) {
                    $e->setPayload($rateLimit->getPayload());
                }

                throw $e;
            }

            $message = $this->getParameter('rate_response_message');
            $code = $this->getParameter('rate_response_code');
            $event->setController(function () use ($message, $code) {
                // @codeCoverageIgnoreStart
                return new Response($message, $code);
                // @codeCoverageIgnoreEnd
            });
            $event->stopPropagation();
        }

    }


    /**
     * @param RateLimit[] $annotations
     */
    protected function findBestMethodMatch(Request $request, array $annotations)
    {
        // Empty array, check the path limits
        if (count($annotations) == 0) {
            return $this->pathLimitProcessor->getRateLimit($request);
        }

        $best_match = null;
        foreach ($annotations as $annotation) {
            // cast methods to array, even method holds a string
            $methods = is_array($annotation->getMethods()) ? $annotation->getMethods() : array($annotation->getMethods());

            if (in_array($request->getMethod(), $methods)) {
                $best_match = $annotation;
            }

            // Only match "default" annotation when we don't have a best match
            if (count($annotation->getMethods()) == 0 && $best_match == null) {
                $best_match = $annotation;
            }
        }

        return $best_match;
    }

    /**
     * @param ControllerEvent|FilterControllerEvent $event
     * @param RateLimit $rateLimit
     * @param array $annotations
     * @return string
     */
    private function getKey($event, RateLimit $rateLimit, array $annotations)
    {
        // Let listeners manipulate the key
        $keyEvent = new GenerateKeyEvent($event->getRequest(), '', $rateLimit->getPayload());

        $rateLimitMethods = implode('.', $rateLimit->getMethods());
        $keyEvent->addToKey($rateLimitMethods);

        $rateLimitAlias = count($annotations) === 0
            ? str_replace('/', '.', $this->pathLimitProcessor->getMatchedPath($event->getRequest()))
            : $this->getAliasForRequest($event);
        $keyEvent->addToKey($rateLimitAlias);

        $this->dispatch(RateLimitEvents::GENERATE_KEY, $keyEvent);

        return $keyEvent->getKey();
    }

    /**
     * @param string $route
     * @param ControllerEvent|FilterControllerEvent $controller
     * @return mixed|string
     */
    private function getAliasForRequest($event)
    {
        if (($route = $event->getRequest()->attributes->get('_route'))) {
            return $route;
        }

        $controller = $event->getController();

        if (is_string($controller) && false !== strpos($controller, '::')) {
            $controller = explode('::', $controller);
        }

        if (is_array($controller)) {
            return str_replace('\\', '.', is_string($controller[0]) ? $controller[0] : get_class($controller[0])) . '.' . $controller[1];
        }

        if ($controller instanceof \Closure) {
            return 'closure';
        }

        if (is_object($controller)) {
            return str_replace('\\', '.', get_class($controller[0]));
        }

        return 'other';
    }

    private function dispatch($eventName, $event)
    {
        if ($this->eventDispatcher instanceof EventDispatcherInterface) {
            // Symfony >= 4.3
            $this->eventDispatcher->dispatch($event, $eventName);
        } else {
            // Symfony 3.4
            $this->eventDispatcher->dispatch($eventName, $event);
        }
    }

}
