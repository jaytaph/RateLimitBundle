<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\EventListener\BaseListener;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class MockListener extends BaseListener {
}

class BaseListenerTest extends TestCase
{
    public function testSetGetParams()
    {
        $base = new MockListener();

        $base->setParameter('foo', 'bar');
        $this->assertEquals('bar', $base->getParameter('foo'));

        $base->setParameter('foo', 'baz');
        $this->assertEquals('baz', $base->getParameter('foo'));
    }

    public function testDefaultValues()
    {
        $base = new MockListener();

        $base->setParameter('foo', 'bar');
        $this->assertEquals('baz', $base->getParameter('doesnotexist', 'baz'));

        $this->assertEquals('bar', $base->getParameter('foo', 'baz'));
    }
}
