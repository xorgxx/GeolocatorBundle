<?php

    namespace NeoxDashBoard\NeoxDashBoardBundle\DependencyInjection\Config;

    class doctrineExtensionsConfig
    {
        public static function getConfig(): array
        {
            return [
                'orm' => [
                    'entity_managers' => [
                        'default' => [
                            'mappings' => [
                                'gedmo_translatable' => [
                                    'type'      => 'attribute', // Change this to 'attribute'
                                    'prefix'    => 'Gedmo\Translatable\Entity',
                                    'dir'       => '%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Translatable/Entity',
                                    'alias'     => 'GedmoTranslatable',
                                    'is_bundle' => false,
                                ],
                                'gedmo_translator' => [
                                    'type'      => 'attribute', // Change this to 'attribute'
                                    'prefix'    => 'Gedmo\Translator\Entity',
                                    'dir'       => '%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Translator/Entity',
                                    'alias'     => 'GedmoTranslator',
                                    'is_bundle' => false,
                                ],
                                'gedmo_loggable' => [
                                    'type'      => 'attribute', // Change this to 'attribute'
                                    'prefix'    => 'Gedmo\Loggable\Entity',
                                    'dir'       => '%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Loggable/Entity',
                                    'alias'     => 'GedmoLoggable',
                                    'is_bundle' => false,
                                ],
                                'gedmo_tree' => [
                                    'type'      => 'attribute', // Change this to 'attribute'
                                    'prefix'    => 'Gedmo\Tree\Entity',
                                    'dir'       => '%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Tree/Entity',
                                    'alias'     => 'GedmoTree',
                                    'is_bundle' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    }
