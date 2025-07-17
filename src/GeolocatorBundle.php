<?php

namespace GeolocatorBundle;

use GeolocatorBundle\DependencyInjection\Compiler\GeolocatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GeolocatorBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new GeolocatorCompilerPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
