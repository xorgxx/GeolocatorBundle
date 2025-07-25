<?php

namespace GeolocatorBundle\DependencyInjection;

use GeolocatorBundle\DependencyInjection\Config\routerConfig;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class GeolocatorExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Charge la configuration du bundle et initialise les services.
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Configurer le namespace de template Twig pour le bundle
        $container->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../Resources/views' => 'Geolocator'
            ]
        ]);

        // Initialiser les paramètres de disponibilité des services avant la compilation de la configuration
        if (!$container->hasParameter('geolocator.messenger_available')) {
            $container->setParameter('geolocator.messenger_available', class_exists('Symfony\\Component\\Messenger\\MessageBusInterface'));
        }
        if (!$container->hasParameter('geolocator.rabbit_available')) {
            $container->setParameter('geolocator.rabbit_available', class_exists('Symfony\\Component\\Messenger\\Bridge\\Amqp\\Transport\\AmqpTransportFactory'));
        }
        if (!$container->hasParameter('geolocator.redis_messenger_available')) {
            $container->setParameter('geolocator.redis_messenger_available', class_exists('Symfony\\Component\\Messenger\\Bridge\\Redis\\Transport\\RedisTransportFactory'));
        }
        if (!$container->hasParameter('geolocator.mercure_available')) {
            $container->setParameter('geolocator.mercure_available', class_exists('Symfony\\Component\\Mercure\\HubInterface'));
        }

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // set key config as container parameters
        foreach ($config as $key => $value) {
            $container->setParameter('geolocator.'.$key, $value);
        }
        // Définir les paramètres de configuration AVANT de charger les services
        $container->setParameter('geolocator.config', $config);
        $container->setParameter('geolocator.profiler.enabled', $config['profiler']['enabled']);


        // Configuration des providers AVANT de charger les services
        $this->configureProviders($container, $config);

        // Charger la configuration des services APRÈS avoir défini les paramètres
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        // Charger des configurations additionnelles
        try {
            if ($config['admin']['enabled'] ?? false) {
                $loader->load('admin.yaml');
            }

            // Charger la configuration des commandes
            $loader->load('commands.yaml');

            // Charger la configuration de Messenger si disponible
            if ($container->hasParameter('geolocator.messenger_available') &&
                $container->getParameter('geolocator.messenger_available') === true) {
                $loader->load('messenger.yaml');
                $loader->load('services/async.yaml');
            }

            // Charger la configuration du profiler (uniquement en environnement de dev ou test)
            if (in_array($container->getParameter('kernel.environment'), ['dev', 'test'])) {
                $loader->load('profiler.yaml');
            }
        } catch (\Exception $e) {
            // Gestion silencieuse des fichiers manquants en production
            if ($container->getParameter('kernel.environment') !== 'prod') {
                throw $e;
            }
        }

        // Configuration conditionnelle
        if (!$config['enabled']) {
            // Désactiver les services tout en conservant les interfaces
            $container->setParameter('geolocator.enabled', false);
            return;
        }

        // Configuration du stockage
        $storageType = $config['storage']['type'];
        if ($storageType === 'json') {
            if (empty($config['storage']['file'])) {
                throw new \InvalidArgumentException('File path must be provided when using JSON storage');
            }
            $container->setParameter('geolocator.storage', $config['storage']['file']);
        }

        $container->setAlias('geolocator.storage', 'geolocator.storage.' . $storageType);

        // Configuration de Redis si nécessaire
        if ($storageType === 'redis') {
            if (empty($config['storage']['redis_dsn'])) {
                throw new \InvalidArgumentException('Redis DSN must be provided when using Redis storage');
            }
            $container->setParameter('geolocator.storage.redis_dsn', $config['storage']['redis_dsn']);
        }

        // Configuration de la file de messages si nécessaire
        if (isset($config['async']) && $config['async']['enabled']) {
            $container->setParameter('geolocator.async.enabled', true);
            $container->setParameter('geolocator.async.transport', $config['async']['transport'] ?? 'async');

            // Vérifier si le service de bus de messages existe
            if (!$container->has('messenger.bus.default') && class_exists('Symfony\\Component\\Messenger\\MessageBusInterface')) {
                // S'il n'existe pas mais que Messenger est disponible, créer un alias vers le service standard
                if ($container->has('messenger.default_bus')) {
                    $container->setAlias('messenger.bus.default', 'messenger.default_bus');
                } elseif ($container->has('message_bus')) {
                    $container->setAlias('messenger.bus.default', 'message_bus');
                }
            }
        } else {
            $container->setParameter('geolocator.async.enabled', false);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependConfigurations($container);
    }
    private function prependConfigurations(ContainerBuilder $container): void
    {
        $configurations = [
            'router'                   => routerConfig::getConfig(),
        ];

        foreach ($configurations as $extension => $config) {
            $container->prependExtensionConfig($extension, $config);
        }
    }
    /**
     * Configure les providers de géolocalisation en fonction de la configuration.
     */
    private function configureProviders(ContainerBuilder $container, array $config): void
    {
        // Récupérer les fournisseurs activés
        $enabledProviders = [];
        $fallbackProviders = [];

        // Configurer chaque provider spécifiquement
        if (isset($config['providers']['list'])) {
            foreach ($config['providers']['list'] as $name => $providerConfig) {
                // Par défaut, les providers sont activés sauf indication contraire
                $isEnabled = $providerConfig['enabled'] ?? true;
                if ($isEnabled) {
                    $enabledProviders[] = $name;

                    // Définir les paramètres spécifiques pour ce provider
                    $container->setParameter(
                        "geolocator.providers.list.{$name}",
                        $providerConfig
                    );

                    // Ajouter aux providers de fallback si configuré
                    if (isset($providerConfig['fallback']) && $providerConfig['fallback']) {
                        $fallbackProviders[] = $name;
                    }
                }
            }
        }

        // S'assurer qu'au moins un provider est disponible
        if (empty($enabledProviders)) {
            // Activer automatiquement le provider interne en mode de secours
            $container->setParameter('geolocator.provider_fallback_mode', true);
//            $container->setParameter('geolocator.providers.enabled', ['local']);
            $container->setParameter('geolocator.providers.default', 'local');
            $container->setParameter('geolocator.providers.fallback', []);

            // Enregistrer un avertissement dans les logs
            if ($container->has('logger')) {
                $container->getDefinition('logger')
                          ->addMethodCall('warning', [
                              'Aucun fournisseur de géolocalisation externe n\'est activé. Utilisation du provider local de secours.'
                          ]);
            }

            return;
        }

        // Définir le provider par défaut
        $defaultProvider = $config['providers']['default'] ?? 'ipapi';
        if (!in_array($defaultProvider, $enabledProviders)) {
            // Si le provider par défaut n'est pas activé, prendre le premier disponible
            if (!empty($enabledProviders)) {
                $defaultProvider = $enabledProviders[0];

                // Enregistrer un avertissement dans les logs
                if ($container->has('logger')) {
                    $container->getDefinition('logger')
                              ->addMethodCall('warning', [
                                  sprintf(
                                      "Le provider par défaut '%s' n'est pas activé. Utilisation de '%s' à la place.",
                                      $config['providers']['default'] ?? 'ipapi',
                                      $defaultProvider
                                  )
                              ]);
                }
            }
        }

        // Définir les paramètres des providers
        $container->setParameter('geolocator.providers.enabled', $enabledProviders);
        $container->setParameter('geolocator.providers.default', $defaultProvider);
        $container->setParameter('geolocator.providers.fallback', $fallbackProviders);
        $container->setParameter('geolocator.provider_fallback_mode', false); // Mode normal par défaut
    }
}
