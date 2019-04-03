<?php

namespace Tests\Util;

use LogicException;
use Noxlogic\RateLimitBundle\Tests\TestCase;
use Noxlogic\RateLimitBundle\Util\AnnotationLimitProcessor;
use Symfony\Component\HttpFoundation\Request;

class AnnotationLimitProcessorGetAliasTest extends TestCase
{
    public function testGetRateLimitAliasRouteNameSet()
    {
        $annotationPathLimit = new AnnotationLimitProcessor(array(), function () {});
        $request = new Request();
        $request->attributes->set('_route', 'api.users');

        $this->assertEquals('api.users', $annotationPathLimit->getRateLimitAlias($request));
    }

    /**
     * @dataProvider provideControllerCallables
     *
     * @param $testName
     * @param $controllerCallable
     * @param $expected
     */
    public function testGetRateLimitAliasControllerCallable($testName, $controllerCallable, $expected)
    {
        $annotationProcessor = new AnnotationLimitProcessor(array(), $controllerCallable);
        $this->assertEquals($expected, $annotationProcessor->getRateLimitAlias(new Request()), $testName);
    }

    public function provideControllerCallables()
    {
        //Controller examples taken from Symfony\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest
        return array(
            array(
                '"Regular" callable',
                array($this, 'testControllerInspection'),
                'Tests.Util.AnnotationLimitProcessorGetAliasTest.testControllerInspection',
            ),

            array(
                'Closure',
                function () {},
                'closure',
            ),

            array(
                'Static callback as string',
                'Tests\Util\AnnotationLimitProcessorGetAliasTest::staticControllerMethod',
                'Tests.Util.AnnotationLimitProcessorGetAliasTest.staticControllerMethod',
            ),

            array(
                'Static callable with instance',
                array($this, 'staticControllerMethod'),
                'Tests.Util.AnnotationLimitProcessorGetAliasTest.staticControllerMethod',
            ),

            array(
                'Static callable with class name',
                array('Tests\Util\AnnotationLimitProcessorGetAliasTest', 'staticControllerMethod'),
                'Tests.Util.AnnotationLimitProcessorGetAliasTest.staticControllerMethod',
            ),

            array(
                'Callable with instance depending on __call()',
                array($this, 'magicMethod'),
                'Tests.Util.AnnotationLimitProcessorGetAliasTest.magicMethod',
            ),

            array(
                'Callable with class name depending on __callStatic()',
                array('Tests\Util\AnnotationLimitProcessorGetAliasTest', 'magicMethod'),
                'Tests.Util.AnnotationLimitProcessorGetAliasTest.magicMethod',
            ),

            array(
                'Invokable controller',
                $this,
                'Tests.Util.AnnotationLimitProcessorGetAliasTest',
            ),
        );
    }

    /**
     * Dummy method used as controller callable.
     */
    public static function staticControllerMethod()
    {
        throw new LogicException('Unexpected method call');
    }

    /**
     * Magic method to allow non existing methods to be called and delegated.
     */
    public function __call($method, $args)
    {
        throw new LogicException('Unexpected method call');
    }

    /**
     * Magic method to allow non existing methods to be called and delegated.
     */
    public static function __callStatic($method, $args)
    {
        throw new LogicException('Unexpected method call');
    }

    public function __invoke()
    {
        throw new LogicException('Unexpected method call');
    }
}
