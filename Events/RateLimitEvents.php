<?php

namespace Noxlogic\RateLimitBundle\Events;

final class RateLimitEvents
{
        const GENERATE_KEY = 'ratelimit.generate.key';
        const CHECKED_RATE_LIMIT = 'ratelimit.checked.ratelimit';
}
