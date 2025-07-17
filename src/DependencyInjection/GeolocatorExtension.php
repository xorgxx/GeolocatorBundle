<?php

namespace GeolocatorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class GeolocatorExtension extends Extension
{
    /**
     * Charge la configuration du bundle et initialise les services.
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
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

        // Charger la configuration des services
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

            // Charger la configuration du profiler (uniquement en environnement de dev)
            if ($container->getParameter('kernel.environment') === 'dev') {
                $loader->load('profiler.yaml');
            }
        } catch (\Exception $e) {
            // Gestion silencieuse des fichiers manquants en production
            if ($container->getParameter('kernel.environment') !== 'prod') {
                throw $e;
            }
        }

        // Définir les paramètres de configuration
        $container->setParameter('geolocator.config', $config);
        $container->setParameter('geolocator.enabled', $config['enabled']);
        $container->setParameter('geolocator.providers', $config['providers']);
        $container->setParameter('geolocator.storage', $config['storage']);
        $container->setParameter('geolocator.bans', $config['bans']);
        $container->setParameter('geolocator.country_filters', $config['country_filters']);
        $container->setParameter('geolocator.ip_filters', $config['ip_filters']);
        $container->setParameter('geolocator.provider_fallback_mode', false); // Par défaut, désactivé
        $container->setParameter('geolocator.vpn_detection', $config['vpn_detection']);
        $container->setParameter('geolocator.crawler_filter', $config['crawler_filter']);
        $container->setParameter('geolocator.redirect_on_ban', $config['redirect_on_ban']);
        $container->setParameter('geolocator.simulate', $config['simulate']);

        // Configuration conditionnelle
        if (!$config['enabled']) {
            // Désactiver les services tout en conservant les interfaces
            $container->setParameter('geolocator.enabled', false);
            return;
        }

        // Configuration du stockage
        $storageType = $config['storage']['type'];
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

        // Configuration des providers
        $this->configureProviders($container, $config);
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
            $container->setParameter('geolocator.providers.enabled', ['local']);
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
