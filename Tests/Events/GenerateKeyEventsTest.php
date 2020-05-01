<?php

namespace Noxlogic\RateLimitBundle\Tests\Events;

use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GenerateKeyEventsTest extends TestCase
{

    public function testConstruction()
    {
        $request = new Request();
        $event = new GenerateKeyEvent($request, "");

        $this->assertEquals("", $event->getKey());
    }

    public function testRequest()
    {
        $request = new Request();
        $event = new GenerateKeyEvent($request, "");

        $this->assertEquals($request, $event->getRequest());
    }

    public function testPayload()
    {
        $request = new Request();
        $event = new GenerateKeyEvent($request, "", 'bar');

        $this->assertSame('bar', $event->getPayload());
    }

    public function testAddKey()
    {
        $request = new Request();
        $event = new GenerateKeyEvent($request, "foo");

        $this->assertEquals("foo", $event->getKey());

        $event->addToKey("bar");
        $this->assertEquals("foo.bar", $event->getKey());

        $event->addToKey("baz");
        $this->assertEquals("foo.bar.baz", $event->getKey());

        $event->addToKey("");
        $this->assertEquals("foo.bar.baz.", $event->getKey());
    }

    public function testSetKey()
    {
        $request = new Request();
        $event = new GenerateKeyEvent($request, "foo");

        $this->assertEquals("foo", $event->getKey());

        $event->setKey("bar");
        $this->assertEquals("bar", $event->getKey());
    }
}
