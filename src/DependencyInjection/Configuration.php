<?php

namespace GeolocatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('xorg_geolocator');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->scalarNode('redirect_route')->defaultValue('app_blocked')->end()
                ->booleanNode('use_custom_blocked_page')->defaultFalse()->end()
                ->booleanNode('simulate')->defaultFalse()->end()
                ->scalarNode('ban_duration')->defaultValue('3 hours')->end()
                ->integerNode('ping_threshold')->defaultValue(10)->end()
                ->arrayNode('blocked_crawlers')->prototype('scalar')->end()->end()
                ->arrayNode('blockedCountries')->prototype('scalar')->end()->end()
                ->arrayNode('allowedCountries')->prototype('scalar')->end()->end()
                ->arrayNode('blockedRanges')->prototype('scalar')->end()->end()
                ->arrayNode('allowedRanges')->prototype('scalar')->end()->end()
                ->arrayNode('blockedIps')->prototype('scalar')->end()->end()
                ->arrayNode('blockedContinents')->prototype('scalar')->end()->end()
                ->arrayNode('allowedContinents')->prototype('scalar')->end()->end()
                // ASN & ISP
                ->arrayNode('bot_patterns')->prototype('scalar')->end()->end()
                ->booleanNode('challenge_mode')->defaultFalse()->end()
                ->arrayNode('webhooks')->prototype('scalar')->end()->end()
                ->arrayNode('blockedAsns')->prototype('scalar')->end()->end()
                ->arrayNode('allowedAsns')->prototype('scalar')->end()->end()
                ->arrayNode('blockedIsps')->prototype('scalar')->end()->end()
                ->arrayNode('allowedIsps')->prototype('scalar')->end()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

// DSN validation and HTTP client timeout can be configured in services.yaml using http_client.custom
