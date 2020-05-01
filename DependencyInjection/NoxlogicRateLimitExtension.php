<?php

namespace Noxlogic\RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $this->loadServices($container, $config);

    }

    private function loadServices(ContainerBuilder $container, array $config)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->setParameter('noxlogic_rate_limit.enabled', $config['enabled']);

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
                if (isset($config['memcache_client'])) {
                    $service = 'memcache.' . $config['memcache_client'];
                } else {
                    $service = $config['memcache_service'];
                }
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference($service)
                );
                break;
            case 'redis':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\Redis');
                if (isset($config['redis_service'])) {
                    $service = $config['redis_service'];
                } else {
                    $service = 'snc_redis.' . $config['redis_client'];
                }
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference($service)
                );
                break;
            case 'doctrine':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\DoctrineCache');
                if (isset($config['doctrine_provider'])) {
                    $service = 'doctrine_cache.providers.' . $config['doctrine_provider'];
                } else {
                    $service = $config['doctrine_service'];
                }
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference($service)
                );
                break;
            case 'php_redis':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\PhpRedis');
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference($config['php_redis_service'])
                );
                break;
            case 'php_redis_cluster':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\PhpRedisCluster');
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference($config['php_redis_service'])
                );
                break;
            case 'simple_cache':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\SimpleCache');
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference($config['simple_cache_service'])
                );
                break;
            case 'cache':
                $container->setParameter('noxlogic_rate_limit.storage.class', 'Noxlogic\RateLimitBundle\Service\Storage\PsrCache');
                $container->getDefinition('noxlogic_rate_limit.storage')->replaceArgument(
                    0,
                    new Reference($config['cache_service'])
                );
                break;
        }

        if ($config['fos_oauth_key_listener']) {
            $tokenStorageReference = new Reference('security.token_storage');
            $container->getDefinition('noxlogic_rate_limit.oauth_key_generate_listener')->replaceArgument(0, $tokenStorageReference);
        } else {
            $container->removeDefinition('noxlogic_rate_limit.oauth_key_generate_listener');
        }
    }
}
