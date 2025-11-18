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

class NoxlogicRateLimitExtensionTest extends WebTestCase
{
    public function testAreParametersSet(): void
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(array(), $containerBuilder);

        self::assertTrue($containerBuilder->getParameter('noxlogic_rate_limit.enabled'));
        self::assertSame(429, $containerBuilder->getParameter('noxlogic_rate_limit.rate_response_code'));
        self::assertTrue($containerBuilder->getParameter('noxlogic_rate_limit.display_headers'));
        self::assertSame('X-RateLimit-Reset', $containerBuilder->getParameter('noxlogic_rate_limit.headers.reset.name'));
        self::assertFalse($containerBuilder->getParameter('noxlogic_rate_limit.fail_open'));
    }

    public function testStorageEngineParameterProvider(): void
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load([
            'noxlogic_rate_limit' => [
                'storage_engine' => 'doctrine',
                'doctrine_provider' => 'redis_cache',
            ]
        ], $containerBuilder);

        self::assertSame(DoctrineCache::class, $containerBuilder->getParameter('noxlogic_rate_limit.storage.class'));

        $storageDef = $containerBuilder->getDefinition('noxlogic_rate_limit.storage');
        self::assertSame('doctrine_cache.providers.redis_cache', (string)($storageDef->getArgument(0)));
    }

    public function testStorageEngineParameterService(): void
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load([
            'noxlogic_rate_limit' => [
                'storage_engine' => 'doctrine',
                'doctrine_service' => 'my.redis_cache',
            ]
        ], $containerBuilder);

        self::assertSame(DoctrineCache::class, $containerBuilder->getParameter('noxlogic_rate_limit.storage.class'));

        $storageDef = $containerBuilder->getDefinition('noxlogic_rate_limit.storage');
        self::assertSame('my.redis_cache', (string)($storageDef->getArgument(0)));
    }

    public function testParametersWhenDisabled(): void
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load(['enabled' => false], $containerBuilder);

        self::assertSame(429, $containerBuilder->getParameter('noxlogic_rate_limit.rate_response_code'));
    }

    public function testPathLimitsParameter(): void
    {
        $pathLimits = [
            'api' => [
                'path' => 'api/',
                'methods' => ['GET'],
                'limit' => 100,
                'period' => 60
            ]
        ];

        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load([['path_limits' => $pathLimits]], $containerBuilder);

        self::assertSame($pathLimits, $containerBuilder->getParameter('noxlogic_rate_limit.path_limits'));
    }

    public function testFailOpenParameter(): void
    {
        $extension = new NoxlogicRateLimitExtension();
        $containerBuilder = new ContainerBuilder(new ParameterBag());
        $extension->load([['fail_open' => true]], $containerBuilder);

        self::assertTrue($containerBuilder->getParameter('noxlogic_rate_limit.fail_open'));
    }
}
