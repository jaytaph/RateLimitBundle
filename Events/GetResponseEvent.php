<?php

namespace Noxlogic\RateLimitBundle\Events;

use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetResponseEvent extends Event
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RateLimitInfo
     */
    private $rateLimitInfo;

    /**
     * GetResponseEvent constructor.
     *
     * @param Request       $request
     * @param RateLimitInfo $rateLimitInfo
     */
    public function __construct(Request $request, RateLimitInfo $rateLimitInfo)
    {
        $this->request = $request;
        $this->rateLimitInfo = $rateLimitInfo;
    }


    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function hasResponse()
    {
        return null !== $this->response;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return RateLimitInfo
     */
    public function getRateLimitInfo()
    {
        return $this->rateLimitInfo;
    }
}
