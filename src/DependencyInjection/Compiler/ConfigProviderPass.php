<?php

namespace GeolocatorBundle\DependencyInjection\Compiler;

use GeolocatorBundle\Config\ProviderConfig;
use GeolocatorBundle\Service\ConfigAccessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ConfigProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('geolocator.config')) {
            return;
        }

        $config = $container->getParameter('geolocator.config');

        if (!isset($config['providers']) || !isset($config['providers']['list'])) {
            return;
        }

        $providers = $config['providers']['list'];

        // Créer un service de configuration pour chaque provider
        foreach ($providers as $name => $providerConfig) {
            $providerDefinition = new Definition(ProviderConfig::class, [$providerConfig]);
            $container->setDefinition("geolocator.config_provider.{$name}", $providerDefinition);
        }

        // Créer un service de configuration avec ConfigAccessor
        $container->setDefinition('geolocator.config_object', new Definition(ConfigAccessor::class, [$config]));
    }
}
