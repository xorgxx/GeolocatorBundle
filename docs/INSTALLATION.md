# Installation du GeolocatorBundle

## Prérequis

- PHP 8.2 ou supérieur
- Symfony 6.0 ou supérieur

## Installation via Composer

```bash
composer require app/geolocator-bundle
```

## Configuration manuelle (si Flex n'est pas activé)

1. Enregistrez le bundle dans `config/bundles.php` :

```php
return [
    // ...
    App\Bundle\GeolocatorBundle\GeolocatorBundle::class => ['all' => true],
];
```

2. Créez le fichier de configuration dans `config/packages/geolocator.yaml` :

```yaml
geolocator:
    enabled: true
    # Configuration supplémentaire...
```

3. Configurez les routes dans `config/routes/geolocator.yaml` :

```yaml
geolocator:
    resource: '@App\Bundle\GeolocatorBundle/Controller/'
    type: annotation
```

4. Configurez vos variables d'environnement dans `.env.local` :

```
# Clé API pour IPQualityScore (si utilisé)
IPQUALITYSCORE_APIKEY=votre_clé_api

# Redis (si activé)
REDIS_DSN=redis://localhost

# RabbitMQ (si activé)
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

## Vérification de l'installation

1. Vérifiez que le bundle est bien reconnu :

```bash
bin/console debug:container --tag=geolocator.provider
```

2. Testez la commande de vérification des fournisseurs :

```bash
bin/console geolocator:test 8.8.8.8
```

3. Accédez à la page de debug (si disponible) :

```
http://votre-site.local/__geo/debug
```

## Configuration avancée

Consultez la documentation complète pour les options de configuration avancées :

- [Configuration avancée](CONFIGURATION.md)
- [Extensibilité](EXTENSIBILITY.md)
