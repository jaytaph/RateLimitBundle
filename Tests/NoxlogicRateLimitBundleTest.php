<?php

namespace Noxlogic\RateLimitBundle\Tests;

use Noxlogic\RateLimitBundle\NoxlogicRateLimitBundle;

class NoxlogicRateLimitBundleTest extends TestCase
{

    public function testBuild()
    {
//        $container = $this->getMock('\\Symfony\\Component\\DependencyInjection\\ContainerBuilder');
//        $container->expects($this->exactly(0))
//            ->method('addCompilerPass')
//            ->with($this->isInstanceOf('\\Symfony\\Component\\DependencyInjection\\Compiler\\CompilerPassInterface'));
//
        $bundle = new NoxlogicRateLimitBundle();
        $this->assertInstanceOf('Noxlogic\\RateLimitBundle\\NoxlogicRateLimitBundle', $bundle);
//        $bundle->build($container);
    }
}
