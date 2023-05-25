<?php

namespace Noxlogic\RateLimitBundle\Events;

final class RateLimitEvents
{
    public const GENERATE_KEY = 'ratelimit.generate.key';
    public const CHECKED_RATE_LIMIT = 'ratelimit.checked.ratelimit';
}
