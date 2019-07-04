<?php

namespace Noxlogic\RateLimitBundle\Events;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\EventDispatcher\Event as ContractsEvent;
use Symfony\Component\EventDispatcher\Event as ComponentEvent;
use Symfony\Component\HttpFoundation\Request;

if(Kernel::VERSION_ID >= 40300) {
    class CheckedRateLimitEvent extends ContractsEvent
    {

        /**
         * @var Request
         */
        protected $request;

        /**
         * @var RateLimit|null
         */
        protected $rateLimit;

        public function __construct(Request $request, RateLimit $rateLimit = null)
        {
            $this->request = $request;
            $this->rateLimit = $rateLimit;
        }

        /**
         * @return RateLimit|null
         */
        public function getRateLimit()
        {
            return $this->rateLimit;
        }

        /**
         * @param RateLimit|null $rateLimit
         */
        public function setRateLimit(RateLimit $rateLimit = null)
        {
            $this->rateLimit = $rateLimit;
        }

        /**
         * @return Request
         */
        public function getRequest()
        {
            return $this->request;
        }
    }
} else {
    class CheckedRateLimitEvent extends ComponentEvent
    {

        /**
         * @var Request
         */
        protected $request;

        /**
         * @var RateLimit|null
         */
        protected $rateLimit;

        public function __construct(Request $request, RateLimit $rateLimit = null)
        {
            $this->request = $request;
            $this->rateLimit = $rateLimit;
        }

        /**
         * @return RateLimit|null
         */
        public function getRateLimit()
        {
            return $this->rateLimit;
        }

        /**
         * @param RateLimit|null $rateLimit
         */
        public function setRateLimit(RateLimit $rateLimit = null)
        {
            $this->rateLimit = $rateLimit;
        }

        /**
         * @return Request
         */
        public function getRequest()
        {
            return $this->request;
        }
    }
}