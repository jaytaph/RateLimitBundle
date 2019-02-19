<?php

namespace Noxlogic\RateLimitBundle\Tests\Exception;

use Noxlogic\RateLimitBundle\Exception\RateLimitExceptionInterface;

class TestException extends \Exception implements RateLimitExceptionInterface
{
    public $payload;

    public function setPayload($payload)
    {
        $this->payload = $payload;
    }
}
