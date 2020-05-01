<?php

namespace Noxlogic\RateLimitBundle\Tests\EventListener;

use Noxlogic\RateLimitBundle\EventListener\OauthKeyGenerateListener;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class OauthKeyGenerateListenerTest extends TestCase
{
    protected $mockContext;

    public function setUp(): void {
        if (interface_exists('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')) {
            $this->mockContext = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')->getMock();
        } else {
            $this->mockContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')->getMock();
        }

        if (! class_exists('FOS\\OAuthServerBundle\\Security\\Authentication\\Token\\OAuthToken')) {
            $this->markTestSkipped("OAuth not found");
        }
    }

    public function testListener()
    {
        $mockToken = $this->createMockToken();

        $mockContext = $this->mockContext;
        $mockContext
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($mockToken));

        $event = new GenerateKeyEvent(new Request(), 'foo');

        $listener = new OauthKeyGenerateListener($mockContext);
        $listener->onGenerateKey($event);

        $this->assertEquals('foo.mocktoken', $event->getKey());
    }

    public function testListenerWithoutOAuthToken()
    {
        $mockContext = $this->mockContext;
        $mockContext
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue(new \StdClass()));

        $event = new GenerateKeyEvent(new Request(), 'foo');

        $listener = new OauthKeyGenerateListener($mockContext);
        $listener->onGenerateKey($event);

        $this->assertEquals('foo', $event->getKey());
    }

    private function createMockToken()
    {
        $oauthToken = $this->getMockBuilder('FOS\\OAuthServerBundle\\Security\\Authentication\\Token\\OAuthToken')->getMock();
        $oauthToken
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue('mocktoken'))
        ;

        return $oauthToken;
    }
}
