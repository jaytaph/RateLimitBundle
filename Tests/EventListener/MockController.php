<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

class MockController {
    public const RATE_LIMIT_KEY = 'Noxlogic.RateLimitBundle.Tests.EventListener.MockController.mockAction';

    public function mockAction(): void { }
}