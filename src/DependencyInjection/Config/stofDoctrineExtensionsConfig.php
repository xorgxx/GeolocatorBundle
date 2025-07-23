<?php

    namespace NeoxDashBoard\NeoxDashBoardBundle\DependencyInjection\Config;

    class stofDoctrineExtensionsConfig
    {
        public static function getConfig(): array
        {
            return [
                'default_locale' => 'fr_FR',
                'orm'            => [
                    'default' => [
                        'sluggable'     => true,
                        'tree'          => true,
                        'timestampable' => true,
                        'translatable'  => true,
                        'loggable'      => true,
                        'blameable'     => true,
                        'sortable'      => true,
                    ],
                    'other'   => [
                        'sluggable' => false,
                    ],
                ],
                'uploadable'     => [
                    'default_file_path'       => '%kernel.project_dir%/public/uploads',
                    'mime_type_guesser_class' => 'Stof\DoctrineExtensionsBundle\Uploadable\MimeTypeGuesserAdapter',
                    'default_file_info_class' => 'Stof\DoctrineExtensionsBundle\Uploadable\UploadedFileInfo',
                ],
            ];
        }
    }
