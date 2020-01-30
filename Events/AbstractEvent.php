<?php

namespace Noxlogic\RateLimitBundle\Events;

use Symfony\Component\EventDispatcher\Event as LegacyEvent;
use Symfony\Contracts\EventDispatcher\Event;

if (!class_exists('Symfony\\Contracts\\EventDispatcher\\Event')) {
    /**
     * Symfony 3.4
     */
    abstract class AbstractEvent extends LegacyEvent
    {
    }
} else {
    /**
     * Symfony >= 4.3
     */
    abstract class AbstractEvent extends Event
    {
    }
}
