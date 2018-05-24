<?php

namespace Noxlogic\RateLimitBundle\Tests\Annotation;

use Noxlogic\RateLimitBundle\Annotation;
use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Tests\TestCase;

class RateLimitTest extends TestCase
{
    public function testConstruction()
    {
        $annot = new RateLimit(array());

        $this->assertEquals('x-rate-limit', $annot->getAliasName());
        $this->assertTrue($annot->allowArray());

        $this->assertEquals(-1, $annot->getLimit());
        $this->assertEmpty($annot->getMethods());
        $this->assertEquals(3600, $annot->getPeriod());
    }

    public function testConstructionWithValues()
    {
        $annot = new RateLimit(array('limit' => 1234, 'period' => 1000));
        $this->assertEquals(1234, $annot->getLimit());
        $this->assertEquals(1000, $annot->getPeriod());


        $annot = new RateLimit(array('methods' => 'POST', 'limit' => 1234, 'period' => 1000));
        $this->assertEquals(1234, $annot->getLimit());
        $this->assertEquals(1000, $annot->getPeriod());
        $this->assertEquals(['POST'], $annot->getMethods());
    }

    public function testConstructionWithMethods()
    {
        $annot = new RateLimit(array('limit' => 1234, 'period' => 1000, 'methods' => array('POST', 'GET')));
        $this->assertCount(2, $annot->getMethods());

        $annot->setMethods(array());
        $this->assertCount(0, $annot->getMethods());
    }
}
