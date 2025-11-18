<?php

namespace Noxlogic\RateLimitBundle\EventListener;

abstract class BaseListener
{

    /**
     * @var array Default parameters which can be used in the listener methods.
     */
    protected $parameters;

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }
}
