<?php


namespace Noxlogic\RateLimitBundle\Util;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Symfony\Component\HttpFoundation\Request;

class PathLimitProcessor
{
    private $pathLimits;

    function __construct(array $pathLimits)
    {
        $this->pathLimits = $pathLimits;

        // Clean up any extra slashes from the config
        foreach ($this->pathLimits as &$pathLimit) {
            $pathLimit['path'] = trim($pathLimit['path'], '/');
        }

        // Order the configs so that the most specific paths
        // are matched first
        usort($this->pathLimits, function($a, $b) {
            return substr_count($b['path'], '/') - substr_count($a['path'], '/');
        });
    }

    public function getRateLimit(Request $request)
    {
        $path = trim(urldecode($request->getPathInfo()), '/');
        $method = $request->getMethod();

        foreach ($this->pathLimits as $pathLimit) {
            if ($this->requestMatched($pathLimit, $path, $method)) {
                return new RateLimit(array(
                    'limit' => $pathLimit['limit'],
                    'period' => $pathLimit['period'],
                    'methods' => $pathLimit['methods']
                ));
            }
        }

        return null;
    }

    public function getMatchedPath(Request $request)
    {
        $path = trim($request->getPathInfo(), '/');
        $method = $request->getMethod();

        foreach ($this->pathLimits as $pathLimit) {
            if ($this->requestMatched($pathLimit, $path, $method)) {
                return $pathLimit['path'];
            }
        }

        return '';
    }

    private function requestMatched($pathLimit, $path, $method)
    {
       return $this->methodMatched($pathLimit['methods'], $method)
            && $this->pathMatched($pathLimit['path'], $path);
    }

    private function methodMatched(array $expectedMethods, $method)
    {
        foreach ($expectedMethods as $expectedMethod) {
            if ($expectedMethod === '*' || $expectedMethod === $method) {
                return true;
            }
        }

        return false;
    }

    private function pathMatched($expectedPath, $path)
    {
        $expectedParts = explode('/', $expectedPath);
        $actualParts = explode('/', $path);

        if (sizeof($actualParts) < sizeof($expectedParts)) {
            return false;
        }

        foreach ($expectedParts as $key => $value) {
            if ($value !== $actualParts[$key]) {
                return false;
            }
        }

        return true;
    }
} 