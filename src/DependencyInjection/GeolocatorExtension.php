<?php

namespace GeolocatorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GeolocatorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

//        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
//        $loader->load('services.yaml');

        // Ajoute cette condition pour stopper le chargement
        if (isset($config['enabled']) && $config['enabled'] === false) {
            return; // Interrompt immédiatement le chargement du bundle
        }

        $container->setParameter('geolocator.enabled', $config['enabled']);
        $container->setParameter('geolocator.redis_enabled', $config['redis_enabled']);
        $container->setParameter('geolocator.rabbit_enabled', $config['rabbit_enabled']);
        $container->setParameter('geolocator.cache_pool', $config['cache_pool']);
        $container->setParameter('geolocator.cache_ttl', $config['cache_ttl']);
        $container->setParameter('geolocator.redirect_route', $config['redirect_route']);
        $container->setParameter('geolocator.use_custom_blocked_page', $config['use_custom_blocked_page']);
        $container->setParameter('geolocator.simulate', $config['simulate']);
        $container->setParameter('geolocator.ban_duration', $config['ban_duration']);
        $container->setParameter('geolocator.ping_threshold', $config['ping_threshold']);
        $container->setParameter('geolocator.messenger_transport', $config['messenger_transport']);
        $container->setParameter('geolocator.ip_filter_flags', $config['ip_filter_flags']);

        // Filtrage IP
        $container->setParameter('geolocator.blocked_crawlers', $config['blocked_crawlers']);
        $container->setParameter('geolocator.blocked_ips', $config['blocked_ips']);
        $container->setParameter('geolocator.blocked_ranges', $config['blocked_ranges']);
        $container->setParameter('geolocator.allowed_ranges', $config['allowed_ranges']);

        // Géolocalisation
        $container->setParameter('geolocator.blocked_countries', $config['blocked_countries']);
        $container->setParameter('geolocator.allowed_countries', $config['allowed_countries']);
        $container->setParameter('geolocator.blocked_continents', $config['blocked_continents']);
        $container->setParameter('geolocator.allowed_continents', $config['allowed_continents']);

        // ASN & FAI
        $container->setParameter('geolocator.blocked_asns', $config['blocked_asns']);
        $container->setParameter('geolocator.allowed_asns', $config['allowed_asns']);
        $container->setParameter('geolocator.blocked_isps', $config['blocked_isps']);
        $container->setParameter('geolocator.allowed_isps', $config['allowed_isps']);

        // Webhooks
        $container->setParameter('geolocator.webhooks', $config['webhooks'] ?? []);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Si RabbitMQ est activé, configurer les tags de service appropriés
        if ($config['rabbit_enabled']) {
            // Les tags et configurations spécifiques pourraient être ajoutés ici
        }
    }
}
//namespace GeolocatorBundle\DependencyInjection;
//
//use Exception;
//use Symfony\Component\DependencyInjection\ContainerBuilder;
//use Symfony\Component\DependencyInjection\Extension\Extension;
//use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
//use Symfony\Component\Config\FileLocator;
//
//
//class GeolocatorExtension extends Extension
//{
//    /**
//     * @throws Exception
//     */
//    public function load(array $configs, ContainerBuilder $container): void
//    {
//        $configuration = new Configuration();
//        $config = $this->processConfiguration($configuration, $configs);
//
//        $container->setParameter('geolocator.config', $config);
//
//        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
//        $loader->load('services.yaml');
//    }
//}
