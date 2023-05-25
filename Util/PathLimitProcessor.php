<?php


namespace Noxlogic\RateLimitBundle\Util;

use Noxlogic\RateLimitBundle\Attribute\RateLimit ;
use Symfony\Component\HttpFoundation\Request;

class PathLimitProcessor
{
    private array $pathLimits;

    public function __construct(array $pathLimits)
    {
        $this->pathLimits = $pathLimits;

        // Clean up any extra slashes from the config
        foreach ($this->pathLimits as &$pathLimit) {
            $pathLimit['path'] = trim($pathLimit['path'], '/');
        }

        // Order the configs so that the most specific paths
        // are matched first
        usort($this->pathLimits, static function($a, $b) {
            return substr_count($b['path'], '/') - substr_count($a['path'], '/');
        });
    }

    public function getRateLimit(Request $request): ?RateLimit
    {
        $path = trim(urldecode($request->getPathInfo()), '/');
        $method = $request->getMethod();

        foreach ($this->pathLimits as $pathLimit) {
            if ($this->requestMatched($pathLimit, $path, $method)) {
                return new RateLimit(
                    $pathLimit['methods'],
                    $pathLimit['limit'],
                    $pathLimit['period']
                );
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

    private function requestMatched($pathLimit, $path, $method): bool
    {
       return $this->methodMatched($pathLimit['methods'], $method)
            && $this->pathMatched($pathLimit['path'], $path);
    }

    private function methodMatched(array $expectedMethods, $method): bool
    {
        foreach ($expectedMethods as $expectedMethod) {
            if ($expectedMethod === '*' || $expectedMethod === $method) {
                return true;
            }
        }

        return false;
    }

    private function pathMatched($expectedPath, $path): bool
    {
        if ($expectedPath === '*') {
            return true;
        }

        $expectedParts = explode('/', $expectedPath);
        $actualParts = explode('/', $path);

        if (count($actualParts) < count($expectedParts)) {
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
