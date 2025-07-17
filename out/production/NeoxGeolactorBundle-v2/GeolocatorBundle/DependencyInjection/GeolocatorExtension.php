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

        // Charger la configuration des services
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Définir les paramètres de configuration
        $container->setParameter('geolocator.config', $config);
        $container->setParameter('geolocator.enabled', $config['enabled']);
        $container->setParameter('geolocator.providers', $config['providers']);
        $container->setParameter('geolocator.storage', $config['storage']);
        $container->setParameter('geolocator.bans', $config['bans']);
        $container->setParameter('geolocator.country_filters', $config['country_filters']);
        $container->setParameter('geolocator.ip_filters', $config['ip_filters']);
        $container->setParameter('geolocator.vpn_detection', $config['vpn_detection']);
        $container->setParameter('geolocator.crawler_filter', $config['crawler_filter']);
        $container->setParameter('geolocator.redirect_on_ban', $config['redirect_on_ban']);
        $container->setParameter('geolocator.simulate', $config['simulate']);

        // Configuration conditionnelle
        if (!$config['enabled']) {
            return;
        }

        // Configuration du stockage
        $storageType = $config['storage']['type'];
        $container->setAlias('geolocator.storage', 'geolocator.storage.' . $storageType);
    }
}
