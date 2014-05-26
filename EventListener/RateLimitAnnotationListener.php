<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RateLimitAnnotationListener extends BaseListener
{
    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $reader;

    /**
     * @var eventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \Noxlogic\RateLimitBundle\Service\RateLimitService
     */
    protected $rateLimitService;

    /**
     * @param \Doctrine\Common\Annotations\Reader $reader
     * @param RateLimitService                    $rateLimitService
     */
    public function __construct(\Doctrine\Common\Annotations\Reader $reader, EventDispatcherInterface $eventDispatcher, RateLimitService $rateLimitService)
    {
        $this->reader = $reader;
        $this->eventDispatcher = $eventDispatcher;
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // Skip if we aren't the main request
        if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) return;

        // Skip if we are a closure
        if (! is_array($controller = $event->getController())) {
            return;
        }

        // Find the best match
        $annotations = $event->getRequest()->attributes->get('_x-rate-limit', array());
        $annotation = $this->findBestMethodMatch($event->getRequest(), $annotations);

        // No matching annotation found
        if (! $annotation) return;

        // Create an initial key by joining the methods, controller and action together
        $key = join("", $annotation->getMethods()) . ':' .  get_class($controller[0]) . ':' . $controller[1];

        // Let listeners manipulate the key
        $keyEvent = new GenerateKeyEvent($event->getRequest(), $key);
        $this->eventDispatcher->dispatch(RateLimitEvents::GENERATE_KEY, $keyEvent);
        $key = $keyEvent->getKey();


        // Ratelimit the call
        $rateLimitInfo = $this->rateLimitService->limitRate($key);
        if (! $rateLimitInfo) {
            // Create new rate limit entry for this call
            $rateLimitInfo = $this->rateLimitService->createRate($key, $annotation->getLimit(), $annotation->getPeriod());
            if (! $rateLimitInfo) {
                return;
            }
        }


        // Store the current rating info in the request attributes
        $request = $event->getRequest();
        $request->attributes->set('rate_limit_info', $rateLimitInfo);

        // When we exceeded our limit, return a custom error response
        if ($rateLimitInfo->getCalls() > $rateLimitInfo->getLimit()) {
            $message = $this->getParameter('rate_response_message');
            $code = $this->getParameter('rate_response_code');
            $event->setController(function () use ($message, $code) {
                return new Response($message, $code);
            });
        }

    }


    /**
     * @param RateLimit[] $annotations
     */
    protected function findBestMethodMatch(Request $request, array $annotations)
    {
        // Empty array, nothing to match
        if (count($annotations) == 0) return null;

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
}
