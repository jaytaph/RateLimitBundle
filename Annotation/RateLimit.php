<?php

namespace Noxlogic\RateLimitBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
class RateLimit extends ConfigurationAnnotation
{
    /**
     * @var array HTTP Methods protected by this annotation. Defaults to all method
     */
    protected $methods = array();

    /**
     * @var int Number of calls per period
     */
    protected $limit = -1;

    /**
     * @var int Number of seconds of the time period in which the calls can be made
     */
    protected $period = 3600;

    /**
     * @var mixed Generic payload
     */
    protected $payload;

    /**
     * @var bool allow the ratelimiter to fail open on any request where an exception is thrown
     */
    protected $failOpen = false;

    /**
     * Returns the alias name for an annotated configuration.
     *
     * @return string
     */
    public function getAliasName()
    {
        return "x-rate-limit";
    }

    /**
     * Returns whether multiple annotations of this type are allowed
     *
     * @return Boolean
     */
    public function allowArray()
    {
        return true;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param array $methods
     */
    public function setMethods($methods)
    {
        $this->methods = (array) $methods;
    }

    /**
     * @return int
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param int $period
     */
    public function setPeriod($period)
    {
        $this->period = $period;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return bool
     */
    public function failOpen()
    {
        return $this->failOpen;
    }

    /**
     * @param bool $failOpen
     */
    public function setFailOpen($failOpen)
    {
        $this->failOpen = $failOpen;
    }

}
