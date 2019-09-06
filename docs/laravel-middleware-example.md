Laravel comes with ``Illuminate\Routing\Middleware\ThrottleRequests`` middleware. 
Those solution developed to be declared only once in application, applying it twice brings unexpected behavior in headers response.

Example replace ``Illuminate\Routing\Middleware\ThrottleRequests`` with ``Noxlogic\RateLimitBundle`` based on configuration file rate limit rules, 
repeating default ``Illuminate\Routing\Middleware\ThrottleRequests`` behaviour with blocking by client ip.

``rate-limit.php``
```php
<?php

return [
    'api' => [
        'path' => 'api/',
        'methods' => ['*'],
        'limit' => 60,
        'period' => 60
    ]
];
```

``RateLimit.php``
```php
<?php

namespace Project\Middlewares;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Redis\RedisManager;
use Noxlogic\RateLimitBundle\Service\RateLimitInfo;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Service\Storage\Redis;
use Symfony\Component\HttpFoundation\Response;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;

class RateLimit
{
    /**
     * @var RateLimitService
     */
    private $rateLimitService;

    /**
     * @var PathLimitProcessor
     */
    private $pathLimitProcessor;

    public function __construct(RedisManager $redisFactory)
    {
        $rateLimitService = new RateLimitService();
        $rateLimitService->setStorage(new Redis($redisFactory->client()));
        $this->rateLimitService = $rateLimitService;
        $this->pathLimitProcessor = new PathLimitProcessor(config('rate-limit'));
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $rateLimit = $this->pathLimitProcessor->getRateLimit($request);

        $key = trim(join('.', $rateLimit->getMethods()) . '.' . $this->pathLimitProcessor->getRateLimitAlias($request) . '.' . $request->getClientIp(), '.');

        $rateLimitInfo = $this->rateLimitService->getRateLimitInfo($key, $rateLimit);

        // When we exceeded our limit, return a custom error response
        if ($rateLimitInfo->isExceeded()) {
            throw new ThrottleRequestsException(
                'Too Many Attempts.',
                null,
                $this->getHeaders($rateLimitInfo)
            );
        }

        $response = $next($request);

        return $this->addHeaders($response, $rateLimitInfo);
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param Response $response
     * @param RateLimitInfo $rateLimitInfo
     * @return Response
     */
    protected function addHeaders(Response $response, RateLimitInfo $rateLimitInfo)
    {
        $response->headers->add(
            $this->getHeaders($rateLimitInfo)
        );

        return $response;
    }

    /**
     * Get the limit headers information.
     *
     * @param RateLimitInfo $rateLimitInfo
     * @return array
     */
    protected function getHeaders(RateLimitInfo $rateLimitInfo)
    {
        return [
            'X-RateLimit-Limit' => $rateLimitInfo->getLimit(),
            'X-RateLimit-Remaining' => $rateLimitInfo->getRemainingAttempts(),
            'Retry-After' => $rateLimitInfo->getResetTimestamp() - time(),
            'X-RateLimit-Reset' => $rateLimitInfo->getResetTimestamp()
        ];
    }
}
```
``Kernel.php``
```php
    protected $middlewareGroups = [
        'api' => [
            //'throttle:60,1',
            'rate-limit',
        ],
    ];
    
    protected $routeMiddleware = [
        //'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'rate-limit' => \Project\Middlewares\RateLimit::class,
    ];
```
