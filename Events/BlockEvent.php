<?php

namespace Noxlogic\RateLimitBundle\Events;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class BlockEvent extends Event
{
    /**
     * @var RateLimitInfo
     */
    private $rateLimitInfo;

    /**
     * @var Request
     */
    private $request;

    /**
     * BlockEvent constructor.
     *
     * @param RateLimitInfo $rateLimitInfo
     * @param Request       $request
     */
    public function __construct(RateLimitInfo $rateLimitInfo, Request $request)
    {
        $this->rateLimitInfo = $rateLimitInfo;
        $this->request = $request;
    }


    /**
     * @return RateLimitInfo
     */
    public function getRateLimitInfo()
    {
        return $this->rateLimitInfo;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
