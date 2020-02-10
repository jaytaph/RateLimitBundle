<?php

namespace Noxlogic\RateLimitBundle\Tests\DependencyInjection;

use Noxlogic\RateLimitBundle\DependencyInjection\Configuration;
use Noxlogic\RateLimitBundle\DependencyInjection\NoxlogicRateLimitExtension;
use Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache;
use Noxlogic\RateLimitBundle\Tests\WebTestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

/**
 * ConfigurationTest
 */
class NoxlogicRateLimitExtensionTest extends WebTestCase
{
    public function testAreParametersSet()
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(array(), $containerBuilder);

        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.enabled'), true);
        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.rate_response_code'), 429);
        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.display_headers'), true);
        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.headers.reset.name'), 'X-RateLimit-Reset');
    }

    public function testStorageEngineParameterProvider()
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(array(
            'noxlogic_rate_limit' => array(
                'storage_engine' => 'doctrine',
                'doctrine_provider' => 'redis_cache',
            )
        ), $containerBuilder);

        $this->assertEquals('Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache', $containerBuilder->getParameter('noxlogic_rate_limit.storage.class'));

        $storageDef = $containerBuilder->getDefinition('noxlogic_rate_limit.storage');
        $this->assertEquals('doctrine_cache.providers.redis_cache', (string)($storageDef->getArgument(0)));
    }

    public function testStorageEngineParameterService()
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(array(
            'noxlogic_rate_limit' => array(
                'storage_engine' => 'doctrine',
                'doctrine_service' => 'my.redis_cache',
            )
        ), $containerBuilder);

        $this->assertEquals('Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache', $containerBuilder->getParameter('noxlogic_rate_limit.storage.class'));

        $storageDef = $containerBuilder->getDefinition('noxlogic_rate_limit.storage');
        $this->assertEquals('my.redis_cache', (string)($storageDef->getArgument(0)));
    }

    public function testParametersWhenDisabled()
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(array('enabled' => false), $containerBuilder);

        $this->assertEquals(429, $containerBuilder->getParameter('noxlogic_rate_limit.rate_response_code'));
    }

    public function testPathLimitsParameter()
    {
        $pathLimits = array(
            'api' => array(
                'path' => 'api/',
                'methods' => array('GET'),
                'limit' => 100,
                'period' => 60
            )
        );

        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(array(array('path_limits' => $pathLimits)), $containerBuilder);

        $this->assertEquals($containerBuilder->getParameter('noxlogic_rate_limit.path_limits'), $pathLimits);
    }
}
