<?php

namespace Noxlogic\RateLimitBundle\Events;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Symfony\Component\HttpFoundation\Request;

class CheckedRateLimitEvent extends AbstractEvent
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RateLimit|null
     */
    protected $rateLimit;

    public function __construct(Request $request, RateLimit $rateLimit = null)
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
    public function setRateLimit(RateLimit $rateLimit = null)
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
