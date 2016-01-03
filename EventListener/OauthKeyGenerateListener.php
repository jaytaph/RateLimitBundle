<?php

namespace Noxlogic\RateLimitBundle\EventListener;

use Noxlogic\RateLimitBundle\Events\GenerateKeyEvent;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OauthKeyGenerateListener
{
    /**
     * @var SecurityContextInterface|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param $tokenStorage
     */
    public function __construct($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param GenerateKeyEvent $event
     */
    public function onGenerateKey(GenerateKeyEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if (! $token instanceof \FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken) {
            return;
        }

        $event->addToKey($token->getToken());
    }
}
