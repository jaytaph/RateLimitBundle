<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

use Noxlogic\RateLimitBundle\Attribute\RateLimit;

#[RateLimit(limit: 1, period: 10)]
class MockControllerWithAttributes extends MockController {

    #[RateLimit(limit: 10, period: 100)]
    function mockAction() { }
}