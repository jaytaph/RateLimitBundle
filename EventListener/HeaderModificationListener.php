<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class HeaderModificationListener extends BaseListener
{
    public function __construct(array $defaultParameters = [])
    {
        $this->parameters = $defaultParameters;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        // Check if we have a rate-limit-info object in our request attributes. If not, we didn't need to limit.
        $rateLimitInfo = $request->attributes->get('rate_limit_info', null);
        if (! $rateLimitInfo) {
            return;
        }

        // Check if we need to add our x-rate-limits to the headers
        if (! $this->getParameter('display_headers')) {
            return;
        }

        /** @var RateLimitInfo $rateLimitInfo */

        $remaining = $rateLimitInfo->getLimit() - $rateLimitInfo->getCalls();
        if ($remaining < 0) {
            $remaining = 0;
        }

        $response = $event->getResponse();
        $response->headers->set($this->getParameter('header_limit_name'), $rateLimitInfo->getLimit());
        $response->headers->set($this->getParameter('header_remaining_name'), (string) $remaining);
        $response->headers->set($this->getParameter('header_reset_name'), $rateLimitInfo->getResetTimestamp());
    }
}
