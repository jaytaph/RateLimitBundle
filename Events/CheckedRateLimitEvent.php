<?php

namespace Noxlogic\RateLimitBundle\Events;

use Noxlogic\RateLimitBundle\Attribute\RateLimit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class CheckedRateLimitEvent extends Event
{
    protected Request $request;

    protected ?RateLimit $rateLimit;

    public function __construct(Request $request, RateLimit $rateLimit = null)
    {
        $this->request = $request;
        $this->rateLimit = $rateLimit;
    }

    public function getRateLimit(): ?RateLimit
    {
        return $this->rateLimit;
    }

    public function setRateLimit(?RateLimit $rateLimit = null): void
    {
        $this->rateLimit = $rateLimit;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
