<?php

namespace Noxlogic\RateLimitBundle\Events;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class GenerateKeyEvent extends Event
{

    /** @var Request */
    protected $request;

    /** @var string */
    protected $key;

    public function __construct(Request $request, $key = '')
    {
        $this->request = $request;
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param $part
     */
    public function addToKey($part)
    {
        if ($this->key) {
            $this->key .= '.'.$part;
        } else {
            $this->key = $part;
        }
    }
}
