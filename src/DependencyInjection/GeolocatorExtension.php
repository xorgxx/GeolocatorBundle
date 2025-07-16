<?php
namespace GeolocatorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GeolocatorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        if (file_exists(__DIR__ . '/../Resources/config/services.yaml')) {
            $loader->load('services.yaml');
        }

        // Chargement de la config du bundle
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set all configuration parameters
        $container->setParameter('geolocator', $config);
        $container->setParameter('geolocator.simulate', $config['simulate'] ?? false);
        $container->setParameter('geolocator.event_bridge_service', $config['event_bridge_service'] ?? null);
        $container->setParameter('geolocator.providers', $config['providers'] ?? []);
        $container->setParameter('geolocator.storage', $config['storage'] ?? []);
        $container->setParameter('geolocator.bans', $config['bans'] ?? []);
        $container->setParameter('geolocator.country_filters', $config['country_filters'] ?? []);
        $container->setParameter('geolocator.vpn_detection', $config['vpn_detection'] ?? []);
        $container->setParameter('geolocator.crawler_filter', $config['crawler_filter'] ?? []);
        $container->setParameter('geolocator.redirect_on_ban', $config['redirect_on_ban'] ?? '/banned');
        $container->setParameter('geolocator.log_channel', $config['log_channel'] ?? 'geolocator');
        $container->setParameter('geolocator.log_level', $config['log_level'] ?? 'warning');
        $container->setParameter('geolocator.profiler', $config['profiler'] ?? ['enabled' => true]);
        
        // Default parameters that might be referenced in services.yaml
        $container->setParameter('geolocator.ip_filter_flags', FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
        $container->setParameter('geolocator.cache_ttl', 3600);
        $container->setParameter('geolocator.bot_patterns', []);
        $container->setParameter('geolocator.bot_challenge_enabled', false);
        $container->setParameter('geolocator.webhooks', []);
        $container->setParameter('geolocator.messenger_transport', null);
    }

    public function getAlias(): string
    {
        return 'geolocator';
    }
}
