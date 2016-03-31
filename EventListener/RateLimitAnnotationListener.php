<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
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
     * @param RateLimitService                    $rateLimitService
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
        // Skip if we aren't the main request
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        // Skip if we are a closure
        if (! is_array($controller = $event->getController())) {
            return;
        }

        // Find the best match
        $annotations = $event->getRequest()->attributes->get('_x-rate-limit', array());
        $rateLimit = $this->findBestMethodMatch($event->getRequest(), $annotations);

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
        }

        // When we exceeded our limit, return a custom error response
        if ($rateLimitInfo->getCalls() > $rateLimitInfo->getLimit()) {

            // Throw an exception if configured.
            if ($this->getParameter('rate_response_exception')) {
                $class = $this->getParameter('rate_response_exception');
                throw new $class($this->getParameter('rate_response_message'), $this->getParameter('rate_response_code'));
            }

            $message = $this->getParameter('rate_response_message');
            $code = $this->getParameter('rate_response_code');
            $event->setController(function () use ($message, $code) {
                // @codeCoverageIgnoreStart
                return new Response($message, $code);
                // @codeCoverageIgnoreEnd
            });
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

    private function getKey(FilterControllerEvent $event, RateLimit $rateLimit, array $annotations)
    {
        $request = $event->getRequest();
        $controller = $event->getController();

        $rateLimitMethods = join("", $rateLimit->getMethods());
        $rateLimitAlias = count($annotations) === 0
            ? $this->pathLimitProcessor->getMatchedPath($request)
            : get_class($controller[0]) . ':' . $controller[1];

        // Create an initial key by joining the methods and the alias
        $key = $rateLimitMethods . ':' . $rateLimitAlias ;

        // Let listeners manipulate the key
        $keyEvent = new GenerateKeyEvent($event->getRequest(), $key);
        $this->eventDispatcher->dispatch(RateLimitEvents::GENERATE_KEY, $keyEvent);

        return $keyEvent->getKey();
    }
}
