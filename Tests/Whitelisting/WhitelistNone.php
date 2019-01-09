<?php

namespace Noxlogic\RateLimitBundle\Tests\Whitelisting;

use Noxlogic\RateLimitBundle\Whitelisting\WhitelistInterface;
use Symfony\Component\HttpFoundation\Request;

class WhitelistNone implements WhitelistInterface {

    /**
     * @param Request $request
     * @return bool
     */
    public function isWhitelisted(Request $request) {
        return false;
    }
}