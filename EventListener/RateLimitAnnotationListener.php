<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Attribute\RateLimit;
use Noxlogic\RateLimitBundle\Events\CheckedRateLimitEvent;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Exception\RateLimitExceptionInterface;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class RateLimitAnnotationListener extends BaseListener
{
    protected EventDispatcherInterface $eventDispatcher;

    protected RateLimitService $rateLimitService;

    protected PathLimitProcessor $pathLimitProcessor;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RateLimitService $rateLimitService,
        PathLimitProcessor $pathLimitProcessor
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->rateLimitService = $rateLimitService;
        $this->pathLimitProcessor = $pathLimitProcessor;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Skip if the bundle isn't enabled (for instance in test environment)
        if( ! $this->getParameter('enabled', true)) {
            return;
        }

        // Skip if we aren't the main request
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        // RateLimits used to be set by sensio/framework-extra-bundle by reading annotations
        // Tests also use that mechanism, we should probably keep it for retrocompatibility
        $request = $event->getRequest();
        if ($request->attributes->has('_x-rate-limit')) {
            /** @var RateLimit[] $rateLimits */
            $rateLimits = $request->attributes->get('_x-rate-limit', []);
        } else {
            $rateLimits = $this->getRateLimitsFromAttributes($event->getController());
        }
        $rateLimit = $this->findBestMethodMatch($request, $rateLimits);

        // Another treatment before applying RateLimit ?
        $checkedRateLimitEvent = new CheckedRateLimitEvent($request, $rateLimit);
        $this->eventDispatcher->dispatch($checkedRateLimitEvent, RateLimitEvents::CHECKED_RATE_LIMIT);
        $rateLimit = $checkedRateLimitEvent->getRateLimit();

        // No matching RateLimit found
        if (! $rateLimit) {
            return;
        }

        $key = $this->getKey($event, $rateLimit, $rateLimits);

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
     * @param RateLimit[] $rateLimits
     */
    protected function findBestMethodMatch(Request $request, array $rateLimits): ?RateLimit
    {
        // Empty array, check the path limits
        if (count($rateLimits) === 0) {
            return $this->pathLimitProcessor->getRateLimit($request);
        }

        $best_match = null;
        foreach ($rateLimits as $rateLimit) {
            if (in_array($request->getMethod(), $rateLimit->getMethods(), true)) {
                $best_match = $rateLimit;
            }

            // Only match "default" annotation when we don't have a best match
            if ($best_match === null && count($rateLimit->methods) === 0) {
                $best_match = $rateLimit;
            }
        }

        return $best_match;
    }

    /** @param RateLimit[] $rateLimits */
    private function getKey(ControllerEvent $event, RateLimit $rateLimit, array $rateLimits): string
    {
        // Let listeners manipulate the key
        $request = $event->getRequest();
        $keyEvent = new GenerateKeyEvent($request, '', $rateLimit->getPayload());

        $rateLimitMethods = implode('.', $rateLimit->getMethods());
        $keyEvent->addToKey($rateLimitMethods);

        $rateLimitAlias = count($rateLimits) === 0
            ? str_replace('/', '.', $this->pathLimitProcessor->getMatchedPath($request))
            : $this->getAliasForRequest($event);
        $keyEvent->addToKey($rateLimitAlias);
        $this->eventDispatcher->dispatch($keyEvent, RateLimitEvents::GENERATE_KEY);

        return $keyEvent->getKey();
    }

    private function getAliasForRequest(ControllerEvent $event): string
    {
        $route = $event->getRequest()->attributes->get('_route');
        if ($route) {
            return $route;
        }

        $controller = $event->getController();

        if (is_string($controller) && str_contains($controller, '::')) {
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

    /**
     * @return RateLimit[]
     */
    private function getRateLimitsFromAttributes(string|array|object $controller): array
    {
        $rClass = $rMethod = null;
        if (\is_array($controller) && method_exists(...$controller)) {
            $rClass = new \ReflectionClass($controller[0]);
            $rMethod = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (\is_string($controller) && false !== $i = strpos($controller, '::')) {
            $rClass = new \ReflectionClass(substr($controller, 0, $i));
        } elseif (\is_object($controller) && \is_callable([$controller, '__invoke'])) {
            $rMethod = new \ReflectionMethod($controller, '__invoke');
        } else {
            $rMethod = new \ReflectionFunction($controller);
        }

        $attributes = [];
        foreach (array_merge($rClass?->getAttributes() ?? [], $rMethod?->getAttributes() ?? []) as $attribute) {
            if (RateLimit::class === $attribute->getName()) {
                $attributes[] = $attribute->newInstance();
            }
        }

        return $attributes;
    }
}
