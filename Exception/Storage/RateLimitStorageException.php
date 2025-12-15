<?php
declare(strict_types=1);

namespace Noxlogic\RateLimitBundle\Exception\Storage;

/**
 * @internal BC promise does not cover this class. Do not use directly
 */
abstract class RateLimitStorageException extends \Exception implements RateLimitStorageExceptionInterface
{
    public function __construct(string $problemDescription, \Throwable $previous)
    {
        $message = \sprintf('Rate limit storage: %s', $problemDescription);

        if ($previous->getMessage()) {
            $message .= \sprintf(': %s', $previous->getMessage());
        }

        parent::__construct($message, previous: $previous);
    }
}