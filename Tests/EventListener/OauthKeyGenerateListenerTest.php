<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\EventListener\OauthKeyGenerateListener;
use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class OauthKeyGenerateListenerTest extends TestCase
{

    public function setUp() {
        if (! class_exists('FOS\\OAuthServerBundle\\Security\\Authentication\\Token\\OAuthToken')) {
            $this->markTestSkipped('FOSOAuth bundle is not found');
        }
    }

    public function testListener()
    {
        $mockToken = $this->createMockToken();

        $mockContext = $this->getMock('Symfony\\Component\\Security\\Core\\SecurityContextInterface');
        $mockContext
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($mockToken));

        $event = new GenerateKeyEvent(new Request(), 'foo');

        $listener = new OauthKeyGenerateListener($mockContext);
        $listener->onGenerateKey($event);

        $this->assertEquals('foo:mocktoken', $event->getKey());
    }


    public function testListenerWithoutOAuthToken()
    {
        $mockContext = $this->getMock('Symfony\\Component\\Security\\Core\\SecurityContextInterface');
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
        $oauthToken = $this->getMock('FOS\\OAuthServerBundle\\Security\\Authentication\\Token\\OAuthToken');
        $oauthToken
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue('mocktoken'))
        ;

        return $oauthToken;
    }
}
