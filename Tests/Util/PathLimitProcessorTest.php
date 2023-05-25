<?php


namespace Noxlogic\RateLimitBundle\Tests\Util;


use Noxlogic\RateLimitBundle\Tests\TestCase;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;

use Symfony\Component\HttpFoundation\Request;

class PathLimitProcessorTest extends TestCase
{

    /** @test */
    public function itReturnsNullIfThereAreNoPathLimits(): void
    {
        $plp = new PathLimitProcessor(array());

        $result = $plp->getRateLimit(new Request());

        $this->assertNull($result);
    }

    /** @test */
    public function itReturnARateLimitIfItMatchesPathAndMethod(): void
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
            'Noxlogic\RateLimitBundle\Attribute\RateLimit',
            $result
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
        $this->assertEquals(array('GET'), $result->getMethods());
    }

    /** @test */
    public function itReturnARateLimitIfItMatchesSubPathWithUrlEncodedString()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $result = $plp->getRateLimit(
            Request::create('%2Fapi%2Fusers', 'GET')
        );

        $this->assertInstanceOf(
            'Noxlogic\RateLimitBundle\Attribute\RateLimit',
            $result
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
        $this->assertEquals(array('GET'), $result->getMethods());
    }

    /** @test */
    public function itWorksWhenMultipleMethodsAreSpecified(): void
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
        $this->assertEquals(array('GET', 'POST'), $result->getMethods());
    }

    /** @test */
    public function itReturnsTheCorrectRateLimitWithMultiplePathLimits(): void
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
        $this->assertEquals(array('POST'), $result->getMethods());
    }

    /** @test */
    public function itWorksWithLimitsOnSamePathButDifferentMethods(): void
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
        $this->assertEquals(array('POST'), $result->getMethods());
    }

    /** @test */
    public function itMatchesAstrixAsAnyMethod(): void
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
        $this->assertEquals(array('*'), $result->getMethods());

        $result = $plp->getRateLimit(
            Request::create('/api/users/emails', 'PUT')
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
        $this->assertEquals(array('*'), $result->getMethods());

        $result = $plp->getRateLimit(
            Request::create('/api/users/emails', 'POST')
        );

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
        $this->assertEquals(array('*'), $result->getMethods());
    }

    /** @test */
    function itMatchesAstrixAsAnyPath()
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => '*',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $result = $plp->getRateLimit(Request::create('/api'));

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
        $this->assertEquals(array('GET'), $result->getMethods());

        $result = $plp->getRateLimit(Request::create('/api/users'));

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
        $this->assertEquals(array('GET'), $result->getMethods());

        $result = $plp->getRateLimit(Request::create('/api/users/emails'));

        $this->assertEquals(100, $result->getLimit());
        $this->assertEquals(60, $result->getPeriod());
        $this->assertEquals(array('GET'), $result->getMethods());
    }

    /** @test */
    public function itMatchesWhenAccessSubPaths(): void
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
        $this->assertEquals(array('GET'), $result->getMethods());
    }

    /** @test */
    public function itReturnsNullIfThereIsNoMatchingPath(): void
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
    public function itMatchesTheMostSpecificPathFirst(): void
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
        $this->assertEquals(array('GET'), $result->getMethods());
    }

    /** @test */
    public function itReturnsTheMatchedPath(): void
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET', 'POST'),
                'limit' => 1000,
                'period' => 600
            )
        ));

        $path = $plp->getMatchedPath(
            Request::create('/api/', 'POST')
        );

        $this->assertEquals('api', $path);
    }

    /** @test */
    public function itReturnsTheCorrectPathForADifferentSetup(): void
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

        $path = $plp->getMatchedPath(
            Request::create('/api/users/emails', 'GET')
        );

        $this->assertEquals('api/users/emails', $path);
    }

    /** @test */
    public function itReturnsTheCorrectMatchedPathForSubPaths(): void
    {
        $plp = new PathLimitProcessor(array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        ));

        $path = $plp->getMatchedPath(
            Request::create('/api/users/emails', 'GET')
        );

        $this->assertEquals('api', $path);
    }
}
 