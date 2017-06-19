<?php

namespace Noxlogic\RateLimitBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 */
class Configuration implements ConfigurationInterface
{
    const HTTP_TOO_MANY_REQUESTS = 429;

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('noxlogic_rate_limit')
            ->canBeDisabled()
            ->children()
                ->enumNode('storage_engine')
                    ->values(array('redis','memcache','doctrine','beryllium_memcache'))
                    ->defaultValue('redis')
                    ->info('The storage engine where all the rates will be stored')
                ->end()
                ->scalarNode('redis_client')
                    ->defaultValue('default_client')
                    ->info('The redis client to use for the redis storage engine')
                ->end()
                ->scalarNode('memcache_client')
                    ->defaultValue('default')
                    ->info('The memcache client to use for the memcache storage engine')
                ->end()
                ->scalarNode('beryllium_memcache_client')
                    ->defaultValue('beryllium_cache')
                    ->info('The beryllium memcache client to use for the memcache storage engine')
                ->end()
                ->scalarNode('doctrine_provider')
                    ->defaultNull()
                    ->info('The Doctrine Cache provider to use for the doctrine storage engine')
                    ->example('my_apc_cache')
                ->end()
                ->integerNode('rate_response_code')
                    ->min(400)
                    ->max(499)
                    ->defaultValue(static::HTTP_TOO_MANY_REQUESTS)
                    ->info('The HTTP status code to return when a client hits the rate limit')
                ->end()
                ->scalarNode('rate_response_exception')
                    ->defaultNull()
                    ->info('Optional exception class that will be returned when a client hits the rate limit')
                    ->validate()
                        ->always(function ($item) {
                            if (! is_subclass_of($item, '\Exception')) {
                                throw new InvalidConfigurationException(sprintf("'%s' must inherit the \\Exception class", $item));
                            }
                            return $item;
                        })
                    ->end()
                ->end()
                ->scalarNode('rate_response_message')
                    ->defaultValue('You exceeded the rate limit')
                    ->info('The HTTP message to return when a client hits the rate limit')
                ->end()
                ->booleanNode('display_headers')
                    ->defaultTrue()
                    ->info('Should the ratelimit headers be automatically added to the response?')
                ->end()
                ->arrayNode('headers')
                    ->addDefaultsIfNotSet()
                    ->info('What are the different header names to add')
                    ->children()
                        ->scalarNode('limit')->defaultValue('X-RateLimit-Limit')->end()
                        ->scalarNode('remaining')->defaultValue('X-RateLimit-Remaining')->end()
                        ->scalarNode('reset')->defaultValue('X-RateLimit-Reset')->end()
                    ->end()
                ->end()
                ->arrayNode('path_limits')
                    ->defaultValue(array())
                    ->info('Rate limits for paths')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('path')
                                ->isRequired()
                            ->end()
                            ->arrayNode('methods')
                                ->prototype('enum')
                                    ->values(array('*', 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'))
                                ->end()
                                ->requiresAtLeastOneElement()
                                ->defaultValue(array('*'))
                            ->end()
                            ->integerNode('limit')
                                ->isRequired()
                                ->min(0)
                            ->end()
                            ->integerNode('period')
                                ->isRequired()
                                ->min(0)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
