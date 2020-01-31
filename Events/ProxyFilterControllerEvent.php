<?php


namespace Noxlogic\RateLimitBundle\Events;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent as LegacyEvent;

if (!class_exists('Symfony\\Component\HttpKernel\\Event\\ControllerEvent')) {
    /**
     * Symfony 3.4
     */
    class ProxyFilterControllerEvent extends LegacyEvent
    {
    }
} else {
    /**
     * Symfony >= 4.3
     */
    class ProxyFilterControllerEvent extends ControllerEvent
    {
    }
}
