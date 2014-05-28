<?php

namespace Noxlogic\RateLimitBundle\Service;

class RateLimitInfo
{
    protected $limit;
    protected $calls;
    protected $resetTimestamp;

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
}
