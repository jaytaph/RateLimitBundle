<?php

namespace Noxlogic\RateLimitBundle\Events;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class CheckedRateLimitEvent extends Event
{

    /** @var Request */
    protected $request;

    /** @var null|RateLimit */
    protected $rateLimit;

    public function __construct(Request $request, ?RateLimit $rateLimit)
    {
        $this->request = $request;
        $this->rateLimit = $rateLimit;
    }

    /**
     * @return RateLimit|null
     */
    public function getRateLimit()
    {
        return $this->rateLimit;
    }

    /**
     * @param RateLimit|null $rateLimit
     */
    public function setRateLimit(?RateLimit $rateLimit)
    {
        $this->rateLimit = $rateLimit;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
