<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;

class OauthKeyGenerateListener
{
    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;


    /**
     * @param SecurityContextInterface $securityContext
     */
    function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }


    /**
     * @param GenerateKeyEvent $event
     */
    public function onGenerateKey(GenerateKeyEvent $event)
    {
        $token = $this->securityContext->getToken();
        if (! $token instanceof \FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken) return;

        $event->addToKey($token->getToken());
    }

}
