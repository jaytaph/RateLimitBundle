<?php

namespace Noxlogic\RateLimitBundle\Events;

use Symfony\Component\HttpFoundation\Request;

class GenerateKeyEvent extends AbstractEvent
{

    /** @var Request */
    protected $request;

    /** @var string */
    protected $key;

    /** @var mixed */
    protected $payload;

    public function __construct(Request $request, $key = '', $payload = null)
    {
        $this->request = $request;
        $this->key = $key;
        $this->payload = $payload;
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

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
