<?php

namespace Noxlogic\RateLimitBundle\Whitelisting;

use Symfony\Component\HttpFoundation\Request;

interface WhitelistInterface {
    /**
     * @param Request $request
     * @return bool
     */
    public function isWhitelisted (Request $request);
}