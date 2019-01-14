<?php

namespace Noxlogic\RateLimitBundle\Exception;

interface RateLimitExceptionInterface
{
    /**
     * @param mixed $payload
     * @return void
     */
    public function setPayload($payload);
}
