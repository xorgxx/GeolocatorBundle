# Mode asynchrone du GeolocatorBundle

Le GeolocatorBundle prend en charge le traitement asynchrone des requêtes de géolocalisation via Symfony Messenger, permettant d'améliorer les performances et la résilience de votre application.

## Fonctionnement

Le mode asynchrone permet de :

1. **Améliorer les performances** : Les requêtes de géolocalisation sont traitées en arrière-plan, évitant ainsi d'augmenter le temps de réponse de votre application
2. **Gérer les pics de charge** : Les requêtes sont mises en file d'attente et traitées selon la capacité du système
3. **Renforcer la résilience** : Même si les services de géolocalisation sont temporairement indisponibles, votre application continue de fonctionner

## Prérequis

Pour utiliser le mode asynchrone, vous devez installer au moins l'un des composants suivants :

```bash
# Installation minimale (Messenger)
composer require symfony/messenger

# Pour utiliser RabbitMQ comme transport
composer require symfony/amqp-messenger

# Pour utiliser Redis comme transport
composer require symfony/redis-messenger predis/predis

# Pour les notifications en temps réel
composer require symfony/mercure-bundle
```

## Configuration

Le bundle détecte automatiquement les composants installés et configure le mode asynchrone en conséquence. Cependant, vous pouvez le configurer manuellement :

```yaml
# config/packages/geolocator.yaml
geolocator:
  # Activer/désactiver la détection automatique des services asynchrones
  auto_detect_services: true

  # Configuration manuelle des transports
  messenger_enabled: true
  rabbit_enabled: false
  redis_messenger_enabled: true
  mercure_enabled: false
```

## Configuration de Symfony Messenger

Vous devez configurer Symfony Messenger pour traiter les messages asynchrones :

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      # Utilisez le transport de votre choix (redis, amqp, doctrine...)
      geolocator_async: '%env(MESSENGER_TRANSPORT_DSN)%'

    routing:
      # Routage des messages vers le transport approprié
      'GeolocatorBundle\Message\GeolocationMessage': geolocator_async
```

## Exemples d'utilisation

### Dans un contrôleur

```php
use GeolocatorBundle\Service\GeolocatorService;

class SomeController
{
    public function someAction(Request $request, GeolocatorService $geolocator)
    {
        // Utilisation asynchrone (mettra la requête en file d'attente)
        $geolocator->getGeoLocationFromRequest($request, true);

        // ... votre code
    }
}
```

### Configuration du worker Messenger

Pour traiter les messages en arrière-plan, vous devez exécuter un worker Messenger :

```bash
# Démarrer un worker Messenger
php bin/console messenger:consume geolocator_async

# En production, avec supervisord
# voir https://symfony.com/doc/current/messenger.html#deploying-to-production
```

## Mode simulation et mode asynchrone

Vous pouvez combiner le mode simulation et le mode asynchrone :

```yaml
# config/packages/geolocator.yaml
geolocator:
  simulate: true
  messenger_enabled: true
```

Dans ce cas :
- Les requêtes seront envoyées en asynchrone
- Le worker traitera les requêtes
- Aucun blocage réel ne sera effectué (simulation)
- Tout sera enregistré dans les logs

## Surveillance et débogage

Les requêtes asynchrones sont enregistrées dans les logs avec les informations suivantes :

```
[info] Tâche de géolocalisation envoyée en asynchrone {"ip":"123.45.67.89"}
[info] Géolocalisation asynchrone réussie {"ip":"123.45.67.89","country":"FR"}
```

Vous pouvez également utiliser le tableau de bord Messenger de Symfony pour surveiller la file d'attente :

```bash
php bin/console messenger:failed:show
php bin/console messenger:stats
```

## Cas d'échec

En cas d'échec du mode asynchrone (Messenger non disponible, erreur d'envoi, etc.), le système utilisera automatiquement le mode synchrone comme fallback pour garantir la continuité du service.
