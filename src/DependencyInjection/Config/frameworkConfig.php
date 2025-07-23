<?php

    namespace NeoxDashBoard\NeoxDashBoardBundle\DependencyInjection\Config;

    /*    
        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__ . '/../../assets/dist/' => "@xorgxx/neox-dashboard-bundle",
                    __DIR__ . '/../../assets/'       => '@neoxDashBoardAssets'
                ],
            ],
        ]);
    */

    class frameworkConfig
    {
        public static function getConfig(): array
        {
            return [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__ . '/../../../assets/dist/'  => "@xorgxx/neox-dashboard-bundle",
                        __DIR__ . '/../../../assets/'       => '@neoxDashBoardAssets',
//                        './assets/bootstrap.js' => '@neoxDashBoardAssets/bootstrap.js'
                    ],
                ],
            ];
        }
        
    }