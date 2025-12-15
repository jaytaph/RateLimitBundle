<?php

namespace Noxlogic\RateLimitBundle\Tests\Attribute;

use Noxlogic\RateLimitBundle\Attribute\RateLimit;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class RateLimitTest extends TestCase
{
    public function testConstruction(): void
    {
        $attribute = new RateLimit();

        self::assertSame(-1, $attribute->limit);
        self::assertEmpty($attribute->methods);
        self::assertSame(3600, $attribute->period);
        self::assertNull($attribute->failOpen);
    }

    public function testConstructionWithValues(): void
    {
        $attribute = new RateLimit(
            [],
            1234,
            1000,
            failOpen: true
        );
        self::assertSame(1234, $attribute->limit);
        self::assertSame(1000, $attribute->period);
        self::assertTrue($attribute->failOpen);

        $attribute = new RateLimit(
            ['POST'],
            1234,
            1000,
            failOpen: false
        );
        self::assertSame(1234, $attribute->limit);
        self::assertSame(1000, $attribute->period);
        self::assertSame(['POST'], $attribute->methods);
        self::assertFalse($attribute->failOpen);
    }

    public function testConstructionWithMethods(): void
    {
        $attribute = new RateLimit(
            ['POST', 'GET'],
            1234,
            1000
        );
        $this->assertCount(2, $attribute->methods);
    }

    public function testConstructWithStringAsMethods(): void
    {
        $attribute = new RateLimit(
            'POST',
            1234,
            1000
        );
        $this->assertEquals(['POST'], $attribute->methods);
    }
}
