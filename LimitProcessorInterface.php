<?php

namespace Noxlogic\RateLimitBundle;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Symfony\Component\HttpFoundation\Request;

interface LimitProcessorInterface
{
    /**
     * @param Request $request
     * @return mixed|RateLimit|null
     */
    public function getRateLimit(Request $request);

    /**
     * @param Request $request
     * @return string
     */
    public function getRateLimitAlias(Request $request);
}
