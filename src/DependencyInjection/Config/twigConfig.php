<?php

    namespace NeoxDashBoard\NeoxDashBoardBundle\DependencyInjection\Config;

    /*    
        $$container->prependExtensionConfig('twig', [
            'paths' => [
                '%kernel.project_dir%\\vendor\\xorgxx\\neox-dashboard-bundle\\src\\Templates' => 'NeoxDashBoardBundle',
            ],
        ]);
    */

    use Symfony\Component\DependencyInjection\ContainerBuilder;

    class twigConfig
    {
        public static function getConfig(): array
        {
            return [
                'paths' => [
                    '%kernel.project_dir%/vendor/xorgxx/neox-dashboard-bundle/src/Templates' => 'NeoxDashBoardBundle',
                ],
            ];
        }
    }