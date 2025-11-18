<?php

namespace Noxlogic\RateLimitBundle\Attribute;

use Symfony\Component\HttpFoundation\Request;

#[\Attribute(\Attribute::IS_REPEATABLE |\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class RateLimit
{
    public function __construct(
        /**
         * @var array<Request::METHOD_*> $methods HTTP Methods protected by this attribute. Defaults to all methods
         *                                        Passing strings is allowed for backward-compatibility but deprecated. Pass an array instead
         */
        public array|string $methods = [],

        /**
         * @var int<-1, max> Number of calls per period
         */
        public int $limit = -1,

        /**
         * @var positive-int Number of seconds of the time period in which the calls can be made
         */
        public int $period = 3600,

        /**
         * @var mixed Generic payload
         */
        public mixed $payload = null
    ) {
        // @RateLimit annotation used to support single method passed as string, keep that for retrocompatibility
        if (!is_array($methods)) {
            $this->methods = [$methods];
        }
    }

    /**
     * @return int<-1, max>
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int<-1, max> $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return array<Request::METHOD_*>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param array<Request::METHOD_*> $methods Passing strings is allowed for backward-compatibility but deprecated. Pass an array instead
     */
    public function setMethods(array|string $methods): void
    {
        $this->methods = (array) $methods;
    }

    /**
     * @return positive-int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @param positive-int $period
     */
    public function setPeriod(int $period): void
    {
        $this->period = $period;
    }

    public function getPayload(): mixed
    {
        return $this->payload;
    }

    public function setPayload(mixed $payload): void
    {
        $this->payload = $payload;
    }
}
