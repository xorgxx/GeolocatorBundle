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

        $rootNode
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->booleanNode('simulate')->defaultFalse()->end()
            ->scalarNode('event_bridge_service')
                ->info('Optional service ID implementing GeolocatorEventBridgeInterface for external event handling')
                ->defaultNull()
            ->end()
            ->arrayNode('providers')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('default')->defaultValue('ipapi')->end()
            ->arrayNode('list')
            ->useAttributeAsKey('name')
            ->arrayPrototype()
            ->children()
            ->scalarNode('dsn')->defaultNull()->end()
            ->scalarNode('apikey')->defaultNull()->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('fallback')
            ->scalarPrototype()->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('storage')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('type')->defaultValue('json')->end()
            ->scalarNode('file')->defaultValue('%kernel.project_dir%/var/bans.json')->end()
            ->scalarNode('redis_dsn')->defaultNull()->end()
            ->end()
            ->end()
            ->arrayNode('bans')
            ->addDefaultsIfNotSet()
            ->children()
            ->integerNode('max_attempts')->defaultValue(10)->end()
            ->integerNode('ttl')->defaultValue(3600)->end()
            ->scalarNode('ban_duration')->defaultValue('1 hour')->end()
            ->arrayNode('permanent_countries')
            ->scalarPrototype()->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('country_filters')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('allow')->scalarPrototype()->end()->end()
            ->arrayNode('block')->scalarPrototype()->end()->end()
            ->end()
            ->end()
            ->arrayNode('vpn_detection')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->scalarNode('provider')->defaultNull()->end()
            ->arrayNode('allowed_ips')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
            ->end()
            ->end()
            ->arrayNode('crawler_filter')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultFalse()->end()
            ->booleanNode('allow_known')->defaultFalse()->end()
            ->end()
            ->end()
            ->scalarNode('redirect_on_ban')->defaultValue('/banned')->end()
            ->scalarNode('log_channel')->defaultValue('geolocator')->end()
            ->scalarNode('log_level')->defaultValue('warning')->end()
            ->arrayNode('profiler')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
