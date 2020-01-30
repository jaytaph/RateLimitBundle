<?php

namespace Noxlogic\RateLimitBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as CurrentWebTestCase;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\WebTestCase as LegacyWebTestCase;

if (!class_exists('Symfony\\Bundle\\FrameworkBundle\\Test\WebTestCase')) {
    /**
     * Old PHPUnit
     */
    abstract class WebTestCase extends LegacyWebTestCase
    {
    }
} else {
    /**
     * New PHPUnit
     */
    abstract class WebTestCase extends CurrentWebTestCase
    {
    }
}
