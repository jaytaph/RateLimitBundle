<?php
declare(strict_types=1);

namespace Noxlogic\RateLimitBundle\Exception\Storage;

/**
 * @internal BC promise does not cover this class. Do not use directly
 */
final class CreateRateRateLimitStorageException extends RateLimitStorageException
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct('Failed to create rate limit', $previous);
    }
}