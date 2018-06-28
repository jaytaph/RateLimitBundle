<?php

namespace Noxlogic\RateLimitBundle\Events;

final class RateLimitEvents
{
        const GENERATE_KEY = 'ratelimit.generate.key';

    /**
     * This event is dispatched after a block happened
     */
    const AFTER_BLOCK = 'ratelimit.block.after';
}
