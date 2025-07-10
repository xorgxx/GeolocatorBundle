<?php

namespace GeolocatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('geolocator');
        $rootNode = $treeBuilder->getRootNode();

        $this->addConfigurationChildren($rootNode);

        return $treeBuilder;
    }

    /**
     * Extrait les enfants de configuration pour le nœud racine.
     */
    private function addConfigurationChildren($rootNode): void
    {
        $rootNode
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->booleanNode('redis_enabled')->defaultFalse()->end()
            ->booleanNode('rabbit_enabled')->defaultFalse()->end()
            ->scalarNode('cache_pool')->defaultValue('cache.app')->end()
            ->scalarNode('cache_ttl')->defaultValue(300)->end()
            ->scalarNode('redirect_route')->defaultValue('app_blocked')->end()
            ->booleanNode('use_custom_blocked_page')->defaultTrue()->end()
            ->booleanNode('simulate')->defaultFalse()->end()
            ->scalarNode('ban_duration')->defaultValue('3 hours')->end()
            ->integerNode('ping_threshold')->defaultValue(10)->end()
            ->integerNode('ip_filter_flags')->defaultValue(8224)->end()
            ->scalarNode('messenger_transport')->defaultNull()->end()
            ->arrayNode('blocked_crawlers')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('blocked_ips')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('blocked_ranges')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allowed_ranges')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('blocked_countries')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allowed_countries')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('blocked_continents')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allowed_continents')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('blocked_asns')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allowed_asns')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('blocked_isps')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allowed_isps')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('webhooks')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;
    }
}

// DSN validation and HTTP client timeout can be configured in services.yaml using http_client.custom
