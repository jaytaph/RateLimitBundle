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
        $container->setParameter('noxlogic_rate_limit.rate_response_exception', $config['rate_response_exception']);
        $container->setParameter('noxlogic_rate_limit.rate_response_code', $config['rate_response_code']);
        $container->setParameter('noxlogic_rate_limit.rate_response_message', $config['rate_response_message']);

        $container->setParameter('noxlogic_rate_limit.display_headers', $config['display_headers']);
        $container->setParameter('noxlogic_rate_limit.headers.limit.name', $config['headers']['limit']);
        $container->setParameter('noxlogic_rate_limit.headers.remaining.name', $config['headers']['remaining']);
        $container->setParameter('noxlogic_rate_limit.headers.reset.name', $config['headers']['reset']);

        $container->setParameter('noxlogic_rate_limit.path_limits', $config['path_limits']);

        switch ($config['storage_engine']) {
            case 'memcache':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\Memcache');
                break;
            case 'redis':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\Redis');
                break;
            case 'doctrine':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache');
                break;
        }

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        switch ($config['storage_engine']) {
            case 'memcache':
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference('memcache.' . $config['memcache_client'])
                );
                break;
            case 'redis':
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference('snc_redis.' . $config['redis_client'])
                );
                break;
            case 'doctrine':
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference('doctrine_cache.providers.' . $config['doctrine_provider'])
                );
                break;
        }

        $container->getDefinition('noxlogic_rate_limit.rate_limit_service')->replaceArgument(0, $$container->getDefinition('noxlogic_rate_limit.storage'));
        $container->getDefinition('noxlogic_rate_limit.oauth_key_generate_listener')->replaceArgument(0, $$container->getDefinition('noxlogic_rate_limit.storage'));
    }
}
