<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\Events\RateLimitEvents;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class RateLimitEventsTest extends TestCase
{
    public function testConstants()
    {
        $this->assertEquals('ratelimit.generate.key', RateLimitEvents::GENERATE_KEY);
        $this->assertEquals('ratelimit.block.after', RateLimitEvents::BLOCK_AFTER);
        $this->assertEquals('ratelimit.response.sending.before', RateLimitEvents::RESPONSE_SENDING_BEFORE);
    }
}
