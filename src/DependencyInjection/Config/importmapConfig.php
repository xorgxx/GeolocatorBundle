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

    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\Filesystem\Filesystem;

    class importmapConfig
    {

        public static function addImportMapConfiguration(ContainerBuilder $container): void
        {
            $projectDir = $container->getParameter('kernel.project_dir');
            $importmapPath = $projectDir . '/importmap.php';

            if (!file_exists($importmapPath)) {
                throw new \RuntimeException('Le fichier importmap.php est introuvable.');
            }

            $content = file_get_contents($importmapPath);
            $entry = <<<PHP
    '@neoxDashBoardAssets/neoxDashBoard' => [
        'path' => './vendor/xorgxx/neox-dashboard-bundle/assets/neoxDashBoard.js',
        'entrypoint' => true,
    ],
PHP;

            // Vérifier si l'entrée existe déjà pour éviter les doublons
            if (!str_contains($content, '@neoxDashBoardAssets/neoxDashBoard')) {
                // Ajouter l'entrée après 'app' => [...]
                $newContent = preg_replace(
                    "/('app' => \[.*?\],)/s",
                    "$1\n$entry",
                    $content
                );

                $filesystem = new Filesystem();
                $filesystem->dumpFile($importmapPath, $newContent);
            }
        }


    }