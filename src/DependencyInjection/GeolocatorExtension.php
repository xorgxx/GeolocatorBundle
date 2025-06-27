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

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Définir les paramètres pour le bundle
        $container->setParameter('geolocator.enabled', $config['enabled']);
        $container->setParameter('geolocator.redis_enabled', $config['redis_enabled']);
        $container->setParameter('geolocator.rabbit_enabled', $config['rabbit_enabled']);
        $container->setParameter('geolocator.cache_pool', $config['cache_pool']);
        $container->setParameter('geolocator.redirect_route', $config['redirect_route']);
        $container->setParameter('geolocator.use_custom_blocked_page', $config['use_custom_blocked_page']);
        $container->setParameter('geolocator.simulate', $config['simulate']);
        $container->setParameter('geolocator.ban_duration', $config['ban_duration']);
        $container->setParameter('geolocator.ping_threshold', $config['ping_threshold']);
        $container->setParameter('geolocator.messenger_transport', $config['messenger_transport']);

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
