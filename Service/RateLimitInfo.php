<?php

namespace Noxlogic\RateLimitBundle\Service;

class RateLimitInfo
{
    protected $limit;
    protected $calls;
    protected $resetTimestamp;

    /**
     * @var bool
     */
    protected $blocked = false;

    /**
     * @var string the key of rate limit
     */
    protected $key;

    /**
     * @return mixed
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param mixed $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * @return mixed
     */
    public function getCalls()
    {
        return $this->calls;
    }

    /**
     * @param mixed $calls
     */
    public function setCalls($calls)
    {
        $this->calls = $calls;
    }

    /**
     * @return mixed
     */
    public function getResetTimestamp()
    {
        return $this->resetTimestamp;
    }

    /**
     * @param mixed $resetTimestamp
     */
    public function setResetTimestamp($resetTimestamp)
    {
        $this->resetTimestamp = $resetTimestamp;
    }

    /**
     * Return true if the action is blocked
     *
     * @return bool
     */
    public function isBlocked()
    {
        return $this->blocked;
    }

    /**
     * Set block the action
     *
     * @param bool $blocked
     */
    public function setBlocked($blocked)
    {
        $this->blocked = (bool)$blocked;
    }

    /**
     * Return the key of rate limit
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the key into rate limit
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = (string)$key;
    }
}
