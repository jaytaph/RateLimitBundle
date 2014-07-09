<?php


namespace Noxlogic\RateLimitBundle\Tests\Util;


use Noxlogic\RateLimitBundle\Tests\TestCase;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;

use Symfony\Component\HttpFoundation\Request;

class PathLimitProcessorTest extends TestCase
{

    /** @test */
    function itReturnsNullIfThereAreNoPathLimits()
    {
        $plp = new PathLimitProcessor(array());

        $result = $plp->getRateLimit(new Request());

        $this->assertNull($result);
    }

    /** @test */
    function itReturnARateLimitIfItMatchesPathAndMethod()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api/', 'GET')
        );

        $this->assertInstanceOf(
            'Noxlogic\RateLimitBundle\Annotation\RateLimit',
            $result
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
    }

    /** @test */
    function itWorksWhenMultipleMethodsAreSpecified()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET', 'POST'),
                'limit' => 1000,
                'period' => 600
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api/', 'POST')
        );

        $this->assertEquals(1000, $result->getLimit());
        $this->assertEquals(600, $result->getPeriod());
    }

    /** @test */
    function itReturnsTheCorrectRateLimitWithMultiplePathLimits()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET', 'POST'),
                'limit' => 1000,
                'period' => 600
            ),
            'api2' => array(
                'path' => 'api2/',
                'methods' => array('POST'),
                'limit' => 20,
                'period' => 15
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api2/', 'POST')
        );

        $this->assertEquals(20, $result->getLimit());
        $this->assertEquals(15, $result->getPeriod());
    }

    /** @test */
    function itWorksWithLimitsOnSamePathButDifferentMethods()
    {
        $plp = new PathLimitProcessor(array(
            'api_get' => array(
                'path' => 'api/',
                'methods' => array('GET'),
                'limit' => 1000,
                'period' => 600
            ),
            'api_post' => array(
                'path' => 'api/',
                'methods' => array('POST'),
                'limit' => 200,
                'period' => 150
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api/', 'POST')
        );

        $this->assertEquals(200, $result->getLimit());
        $this->assertEquals(150, $result->getPeriod());
    }

    /** @test */
    function itMatchesAstrixAsAnyMethod()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('*'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api/users/emails', 'GET')
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());

        $result = $plp->getRateLimit(
            Request::create('/api/users/emails', 'PUT')
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());

        $result = $plp->getRateLimit(
            Request::create('/api/users/emails', 'POST')
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
    }

    /** @test */
    function itMatchesWhenAccessSubPaths()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api/users/emails', 'GET')
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
    }

    /** @test */
    function itReturnsNullIfThereIsNoMatchingPath()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/users/emails',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api', 'GET')
        );

        $this->assertNull($result);
    }

    /** @test */
    function itMatchesTheMostSpecificPathFirst()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api',
                'methods' => array('GET'),
                'limit' => 5,
                'period' => 1
            ),
            'api_emails' => array(
                'path' => 'api/users/emails',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('/api/users/emails', 'GET')
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
    }
}
 