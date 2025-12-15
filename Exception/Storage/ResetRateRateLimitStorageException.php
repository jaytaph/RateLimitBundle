<?php
declare(strict_types=1);

namespace Noxlogic\RateLimitBundle\Exception\Storage;

/**
 * @internal BC promise does not cover this class. Do not use directly
 */
final class ResetRateRateLimitStorageException extends RateLimitStorageException
{
    public function __construct(\Throwable $previous)
    {
        parent::__construct('Failed to reset rate', $previous);
    }
}