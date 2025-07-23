<?php

namespace GeolocatorBundle;

use GeolocatorBundle\DependencyInjection\Compiler\GeolocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use GeolocatorBundle\DependencyInjection\Compiler\ConfigProviderPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GeolocatorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Ajouter le compiler pass pour les services de configuration des providers
        $container->addCompilerPass(new ConfigProviderPass());
        $container->addCompilerPass(new GeolocatorCompilerPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        // Charger automatiquement les routes depuis les contrôleurs
        $routes->import('../Controller/', 'attribute');
    }

}
