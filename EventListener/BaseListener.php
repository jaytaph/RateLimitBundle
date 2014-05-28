<?php

namespace Noxlogic\RateLimitBundle\EventListener;

abstract class BaseListener
{

    /**
     * @var array Default parameters which can be used in the listener methods.
     */
    protected $parameters;

    /**
     * @param $name
     * @param $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @param  $name
     * @param  mixed $default
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }
}
