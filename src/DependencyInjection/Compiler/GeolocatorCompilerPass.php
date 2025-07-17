<?php

namespace GeolocatorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GeolocatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('geolocator.service')) {
            return;
        }

        $definition = $container->getDefinition('geolocator.service');

        // Collecter tous les providers taggés
        $taggedServices = $container->findTaggedServiceIds('geolocator.provider');

        $providers = [];
        foreach ($taggedServices as $id => $tags) {
            $providerName = isset($tags[0]['alias']) 
                ? $tags[0]['alias'] 
                : $this->getProviderNameFromServiceId($id);

            $providers[$providerName] = new Reference($id);
        }

        $definition->setArgument('$providers', $providers);
    }

    private function getProviderNameFromServiceId(string $id): string
    {
        $parts = explode('.', $id);
        return end($parts);
    }
}
