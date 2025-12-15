<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

use Noxlogic\RateLimitBundle\Attribute\RateLimit;

#[RateLimit(limit: 1, period: 10)]
class MockControllerWithAttributes extends MockController {

    #[RateLimit(limit: 10, period: 100)]
    public function mockAction(): void { }

    #[RateLimit(limit: 10, period: 100, failOpen: true)]
    public function failOpenMockAction(): void { }

    #[RateLimit(limit: 10, period: 100, failOpen: false)]
    public function doNotFailOpenMockAction(): void { }
}