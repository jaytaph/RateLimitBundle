<?php


namespace Noxlogic\RateLimitBundle\Events;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent as LegacyEvent;

if (!class_exists('Symfony\\Component\HttpKernel\\Event\\ControllerEvent')) {
    /**
     * Symfony 3.4
     */
    abstract class AbstractFilterControllerEvent extends LegacyEvent
    {
    }
} else {
    /**
     * Symfony >= 4.3
     */
    abstract class AbstractFilterControllerEvent extends ControllerEvent
    {
    }
}
