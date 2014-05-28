<?php

namespace Noxlogic\RateLimitBundle\Tests\DependencyInjection;

use Noxlogic\RateLimitBundle\DependencyInjection\Configuration;
use Noxlogic\RateLimitBundle\DependencyInjection\NoxlogicRateLimitExtension;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * ConfigurationTest
 */
class NoxlogicRateLimitExtensionTest extends WebTestCase
{
    protected $configuration;

    public function setUp()
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $this->configuration = $processor->processConfiguration($configuration, array());
    }

    public function testAreParametersSet()
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(array(), $containerBuilder);

        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.rate_response_code'), 429);
        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.display_headers'), true);
        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.headers.reset.name'), 'X-RateLimit-Reset');
    }
}
