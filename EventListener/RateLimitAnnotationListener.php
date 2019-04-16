<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Events\CheckedRateLimitEvent;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Exception\RateLimitExceptionInterface;
use Noxlogic\RateLimitBundle\LimitProcessorInterface;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Util\AnnotationLimitProcessor;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RateLimitAnnotationListener extends BaseListener
{

    /**
     * @var eventDispatcherInterface
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
     * @param EventDispatcherInterface $eventDispatcher
     * @param RateLimitService $rateLimitService
     * @param PathLimitProcessor $pathLimitProcessor
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RateLimitService $rateLimitService,
        PathLimitProcessor $pathLimitProcessor
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->rateLimitService = $rateLimitService;
        $this->pathLimitProcessor = $pathLimitProcessor;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
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
        $limitProcessor = $this->pathLimitProcessor;
        if ($annotations) {
            $limitProcessor = new AnnotationLimitProcessor($annotations, $event->getController());
        }

        // Another treatment before applying RateLimit ?
        $checkedRateLimitEvent = new CheckedRateLimitEvent($event->getRequest(), $rateLimit);
        $this->eventDispatcher->dispatch(RateLimitEvents::CHECKED_RATE_LIMIT, $checkedRateLimitEvent);
        $rateLimit = $checkedRateLimitEvent->getRateLimit();

        // No matching annotation found
        if (! $rateLimit) {
            return;
        }

        $key = $this->getKey($limitProcessor, $rateLimit, $event->getRequest());

        $rateLimitInfo = $this->rateLimitService->getRateLimitInfo($key, $rateLimit);
        if (!$rateLimitInfo) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        // Store the current rating info in the request attributes
        $request = $event->getRequest();
        $request->attributes->set('rate_limit_info', $rateLimitInfo);

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
     * @param Request $request
     * @param RateLimit[] $annotations
     * @return RateLimit|null
     *
     * @deprecated since 1.15, use the "\Noxlogic\RateLimitBundle\LimitProcessorInterface::getRateLimit()" method instead.
     */
    protected function findBestMethodMatch(Request $request, array $annotations)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since version 1.15, use the "\Noxlogic\RateLimitBundle\LimitProcessorInterface::getRateLimit()" method instead.', __METHOD__), E_USER_DEPRECATED);

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

    private function getKey(LimitProcessorInterface $limitProcessor, RateLimit $rateLimit, Request $request)
    {
        // Let listeners manipulate the key
        $keyEvent = new GenerateKeyEvent($request, '', $rateLimit->getPayload());

        $keyEvent->addToKey(join('.', $rateLimit->getMethods()));
        $keyEvent->addToKey($limitProcessor->getRateLimitAlias($request));

        $this->eventDispatcher->dispatch(RateLimitEvents::GENERATE_KEY, $keyEvent);

        return $keyEvent->getKey();
    }
}
