<?php

namespace Noxlogic\RateLimitBundle\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE |\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class RateLimit
{
    /**
     * @var array HTTP Methods protected by this attribute. Defaults to all method
     */
    public array $methods = [];

    public function __construct(
        $methods = [],

        /**
         * @var int Number of calls per period
         */
        public int $limit = -1,

        /**
         * @var int Number of seconds of the time period in which the calls can be made
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
        } else {
            $this->methods = $methods;
        }
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function setMethods($methods): void
    {
        $this->methods = (array) $methods;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

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
