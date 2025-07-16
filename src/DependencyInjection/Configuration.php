<?php

namespace GeolocatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('geolocator');
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->scalarNode('event_bridge_service')->defaultNull()->end()
            ->arrayNode('ip_filter_flags')
            ->prototype('scalar')->end()
            ->defaultValue(['X-Forwarded-For', 'Client-Ip'])
            ->end()
            ->arrayNode('providers')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('default')->defaultValue('ipapi')->end()
            ->arrayNode('list')
            ->isRequired()
            ->children()
            ->arrayNode('ipapi')
            ->children()
            ->scalarNode('dsn')->isRequired()->cannotBeEmpty()->defaultValue('https://ipapi.co/{ip}/json/')->end()
            ->end()
            ->end()
            ->arrayNode('ipwhois')
            ->children()
            ->scalarNode('dsn')->isRequired()->cannotBeEmpty()->defaultValue('https://ipwhois.app/json/{ip}')->end()
            ->end()
            ->end()
            ->arrayNode('ipqualityscore')
            ->children()
            ->scalarNode('dsn')->isRequired()->cannotBeEmpty()->defaultValue('https://ipqualityscore.com/api/json/ip/{apikey}/{ip}')->end()
            ->scalarNode('apikey')->defaultValue('%env(IPQUALITYSCORE_APIKEY)%')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('fallback')
            ->prototype('scalar')->end()
            ->defaultValue(['ipwhois', 'ipapi'])
            ->end()
            ->end()
            ->end()

            ->arrayNode('storage')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('type')->defaultValue('json')->end()
            ->scalarNode('file')->defaultValue('%kernel.project_dir%/var/bans.json')->end()
            ->scalarNode('redis_dsn')->defaultValue('redis://localhost')->end()
            ->end()
            ->end()

            ->arrayNode('bans')
            ->addDefaultsIfNotSet()
            ->children()
            ->integerNode('max_attempts')->defaultValue(10)->end()
            ->integerNode('ttl')->defaultValue(3600)->end()
            ->scalarNode('ban_duration')->defaultValue('1 hour')->end()
            ->arrayNode('permanent_countries')
            ->prototype('scalar')->end()
            ->defaultValue(['RU', 'CN'])
            ->end()
            ->end()
            ->end()

            ->arrayNode('country_filters')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('allow')
            ->prototype('scalar')->end()
            ->defaultValue(['FR', 'BE'])
            ->end()
            ->arrayNode('block')
            ->prototype('scalar')->end()
            ->defaultValue(['RU', 'CN'])
            ->end()
            ->end()
            ->end()

            ->arrayNode('vpn_detection')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->scalarNode('provider')->defaultValue('ipqualityscore')->end()
            ->arrayNode('allowed_ips')
            ->prototype('scalar')->end()
            ->defaultValue([])
            ->end()
            ->end()
            ->end()

            ->arrayNode('crawler_filter')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
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

            ->booleanNode('simulate')->defaultFalse()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
