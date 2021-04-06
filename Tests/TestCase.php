<?php

namespace Noxlogic\RateLimitBundle\Tests;

use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\Constraint\LogicalNot;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

    // Added for BC reasons
    public static function assertIsArray($actual, string $message = ''): void
    {
        static::assertThat(
            $actual,
            new IsType(IsType::TYPE_ARRAY),
            $message
        );
    }

    public static function assertIsNotArray($actual, string $message = ''): void
    {
        static::assertThat(
            $actual,
            new LogicalNot(new IsType(IsType::TYPE_ARRAY)),
            $message
        );
    }
}
