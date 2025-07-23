<?php

    namespace NeoxDashBoard\NeoxDashBoardBundle\DependencyInjection\Config;

    /*    
            $container->prependExtensionConfig('twig_component', [
                'defaults' => [
                    '%kernel.project_dir%\\vendor\\xorgxx\\neox-dashboard-bundle\\src\\Twig\\Components\\' => '~',
                ],
            ]);
    */

    class twigComponentsConfig
    {
        public static function getConfig(): array
        {
            return [
                'defaults' => [
                    '%kernel.project_dir%\\vendor\\xorgxx\\neox-dashboard-bundle\\src\\Twig\\Components\\' => "~",
                ],
            ];
        }
    }