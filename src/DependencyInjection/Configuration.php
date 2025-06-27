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
            ->booleanNode('enabled')->defaultTrue()->end()
            ->booleanNode('redis_enabled')->defaultFalse()->end()
            ->booleanNode('rabbit_enabled')->defaultFalse()->end()
            ->scalarNode('cache_pool')->defaultValue('cache.app')->end()
            ->scalarNode('messenger_transport')->defaultValue('async')->end()
            ->scalarNode('redirect_route')->defaultValue('app_blocked')->end()
            ->booleanNode('use_custom_blocked_page')->defaultFalse()->end()
            ->booleanNode('simulate')->defaultFalse()->end()
            ->scalarNode('ban_duration')->defaultValue('3 hours')->end()
            ->integerNode('ping_threshold')->defaultValue(10)->end()
            ->arrayNode('blocked_crawlers')->prototype('scalar')->end()->end()
            ->arrayNode('blocked_countries')->prototype('scalar')->end()->end()
            ->arrayNode('allowed_countries')->prototype('scalar')->end()->end()
            ->arrayNode('blocked_ranges')->prototype('scalar')->end()->end()
            ->arrayNode('allowed_ranges')->prototype('scalar')->end()->end()
            ->arrayNode('blocked_ips')->prototype('scalar')->end()->end()
            ->arrayNode('blocked_continents')->prototype('scalar')->end()->end()
            ->arrayNode('allowed_continents')->prototype('scalar')->end()->end()
            ->arrayNode('bot_patterns')->prototype('scalar')->end()->end()
            ->booleanNode('challenge_mode')->defaultFalse()->end()
            ->arrayNode('webhooks')->prototype('scalar')->end()->end()
            ->arrayNode('blocked_asns')->prototype('scalar')->end()->end()
            ->arrayNode('allowed_asns')->prototype('scalar')->end()->end()
            ->arrayNode('blocked_isps')->prototype('scalar')->end()->end()
            ->arrayNode('allowed_isps')->prototype('scalar')->end()->end()
            ->end();
    }
}

// DSN validation and HTTP client timeout can be configured in services.yaml using http_client.custom
