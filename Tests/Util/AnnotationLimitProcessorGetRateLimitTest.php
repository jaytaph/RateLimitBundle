<?php

namespace Noxlogic\RateLimitBundle\Tests\Util;

use Noxlogic\RateLimitBundle\Annotation\RateLimit;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Noxlogic\RateLimitBundle\Util\AnnotationLimitProcessor;
use Symfony\Component\HttpFoundation\Request;

class AnnotationLimitProcessorGetRateLimitTest extends TestCase
{
    /**
     * @param array $annotations
     * @return AnnotationLimitProcessor
     */
    protected function getAnnotationLimitProcessor(array $annotations)
    {
        $controller = function () {};
        $annotationLimitProcess = new AnnotationLimitProcessor($annotations, $controller);

        return $annotationLimitProcess;
    }

    public function testGetRateLimitNotMatchingAnnotations()
    {
        $request = new Request();

        $annotations = array(
            new RateLimit(array('methods' => 'GET', 'limit' => 100, 'period' => 3600)),
        );

        $annotationLimitProcess = $this->getAnnotationLimitProcessor($annotations);

        $request->setMethod('PUT');
        $this->assertNull($annotationLimitProcess->getRateLimit($request));

        $request->setMethod('GET');
        $this->assertEquals(
            $annotations[0],
            $annotationLimitProcess->getRateLimit($request)
        );
    }

    public function testGetRateLimitMatchingMultipleAnnotations()
    {
        $request = new Request();

        $annotations = array(
            new RateLimit(array('methods' => 'GET', 'limit' => 100, 'period' => 3600)),
            new RateLimit(array('methods' => array('GET','PUT'), 'limit' => 200, 'period' => 7200)),
        );

        $annotationLimitProcess = $this->getAnnotationLimitProcessor($annotations);

        $request->setMethod('PUT');
        $this->assertEquals($annotations[1], $annotationLimitProcess->getRateLimit($request));

        $request->setMethod('GET');
        $this->assertEquals($annotations[1], $annotationLimitProcess->getRateLimit($request));
    }

    public function testBestMethodMatch()
    {
        $request = new Request();

        $annotations = array(
            new RateLimit(array('limit' => 100, 'period' => 3600)),
            new RateLimit(array('methods' => 'GET', 'limit' => 100, 'period' => 3600)),
            new RateLimit(array('methods' => array('POST', 'PUT'), 'limit' => 100, 'period' => 3600)),
        );

        $annotationLimitProcess = $this->getAnnotationLimitProcessor($annotations);

        // Find the method that matches the string
        $request->setMethod('GET');
        $this->assertEquals(
            $annotations[1],
            $annotationLimitProcess->getRateLimit($request)
        );

        // Method not found, use the default one
        $request->setMethod('DELETE');
        $this->assertEquals(
            $annotations[0],
            $annotationLimitProcess->getRateLimit($request)
        );

        // Find best match based in methods in array
        $request->setMethod('PUT');
        $this->assertEquals(
            $annotations[2],
            $annotationLimitProcess->getRateLimit($request)
        );
    }

    public function testFindNoAnnotations()
    {
        $request = new Request();

        $annotations = array();

        $annotationLimitProcess = $this->getAnnotationLimitProcessor($annotations);

        $request->setMethod('PUT');
        $this->assertNull($annotationLimitProcess->getRateLimit($request));

        $request->setMethod('GET');
        $this->assertNull($annotationLimitProcess->getRateLimit($request));
    }
}
