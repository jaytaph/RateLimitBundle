<?php

namespace Noxlogic\RateLimitBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class NoxlogicRateLimitExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if ($config['enabled'] === true) {
            $this->loadServices($container, $config);
        }
    }

    private function loadServices(ContainerBuilder $container, array $config)
    {
        $container->setParameter('noxlogic_rate_limit.rate_response_route', $config['rate_response_route']);
        $container->setParameter('noxlogic_rate_limit.rate_response_code', $config['rate_response_code']);
        $container->setParameter('noxlogic_rate_limit.rate_response_message', $config['rate_response_message']);

        $container->setParameter('noxlogic_rate_limit.display_headers', $config['display_headers']);
        $container->setParameter('noxlogic_rate_limit.headers.limit.name', $config['headers']['limit']);
        $container->setParameter('noxlogic_rate_limit.headers.remaining.name', $config['headers']['remaining']);
        $container->setParameter('noxlogic_rate_limit.headers.reset.name', $config['headers']['reset']);

        $container->setParameter('noxlogic_rate_limit.path_limits', $config['path_limits']);

        $container->setParameter(
            'noxlogic_rate_limit.storage.class',
            $config['storage_engine'] === 'memcache'
                ? 'Noxlogic\RateLimitBundle\Service\Storage\Memcache'
                : 'Noxlogic\RateLimitBundle\Service\Storage\Redis'
        );

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
            0,
            $config['storage_engine'] === 'memcache'
                ? new Reference('memcache.' . $config['memcache_client'])
                : new Reference('snc_redis.' . $config['redis_client'])
        );
    }
}
