<?php


namespace Noxlogic\RateLimitBundle\Util;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\LimitProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class PathLimitProcessor implements LimitProcessorInterface
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

    /**
     * @deprecated since version 1.15, use getRateLimitAlias method instead.
     *
     * @param Request $request
     * @return string
     */
    public function getMatchedPath(Request $request)
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since version 1.15, use the "getRateLimitAlias()" method instead.', __METHOD__), E_USER_DEPRECATED);
        return $this->getMatchedLimitPath($request);
    }

    public function getRateLimitAlias(Request $request)
    {
        return str_replace('/', '.', $this->getMatchedLimitPath($request));
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

    /**
     * @param Request $request
     * @return string
     */
    private function getMatchedLimitPath(Request $request)
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
}
