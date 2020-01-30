<?php

namespace Noxlogic\RateLimitBundle\Tests;

if (!class_exists('\\PHPUnit\\Framework\\TestCase')) {
    /**
     * Old PHPUnit
     */
    abstract class TestCase extends \PHPUnit_Framework_TestCase
    {
    }
} else {
    /**
     * New PHPUnit
     */
    abstract class TestCase extends \PHPUnit\Framework\TestCase
    {
    }
}
