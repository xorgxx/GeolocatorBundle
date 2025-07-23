<?php

    namespace GeolocatorBundle\DependencyInjection\Config;
    class routerConfig
    {
        public static function getConfig(): array
        {
            return [
                'controllers_neox_geolocator' => [
                    'resource'  => [
                        'path'      => '../vendor/xorgxx/geolocator-bundle/src/Controller/',
                        'namespace' => 'GeolocatorBundle\GeolocatorBundle\Controller',
                    ],
                    'type'      => 'attribute',
                    // 'prefix' => '/secure', // Ajoutez un préfixe si nécessaire
                ],
            ];
        }
    }