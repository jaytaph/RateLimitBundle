<?php


namespace Noxlogic\RateLimitBundle\Events;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent as LegacyEvent;

if (!class_exists('Symfony\\Component\HttpKernel\\Event\\ResponseEvent')) {
    /**
     * Symfony 3.4
     */
    class ProxyFilterResponseEvent extends LegacyEvent
    {
    }
} else {
    /**
     * Symfony >= 4.3
     */
    class ProxyFilterResponseEvent extends ResponseEvent
    {
    }
}
