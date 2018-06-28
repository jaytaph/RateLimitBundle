<?php

namespace Noxlogic\RateLimitBundle\Events;

final class RateLimitEvents
{
    /**
     * This event is dispatched when generating a key is doing
     */
    const GENERATE_KEY = 'ratelimit.generate.key';

    /**
     * This event is dispatched after a block happened
     */
    const AFTER_BLOCK = 'ratelimit.block.after';
}
