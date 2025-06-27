# Mode Asynchrone

Le GeolocatorBundle supporte le traitement asynchrone des requêtes de géolocalisation via Symfony Messenger. Cela permet d'améliorer les performances de votre application en déléguant le traitement intensif des requêtes géographiques à des workers asynchrones.

## Configuration

Pour activer le mode asynchrone, vous devez configurer RabbitMQ (ou un autre transport compatible avec Symfony Messenger).

### 1. Installer les dépendances

```bash
composer require symfony/messenger symfony/amqp-messenger
```

### 2. Configurer le transport dans `.env`

```dotenv
# .env ou .env.local
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

### 3. Activer le mode asynchrone dans la configuration

```yaml
# config/packages/geolocator.yaml
geolocator:
  rabbit_enabled: true  # Active le mode asynchrone
  messenger_transport: 'async'  # Nom du transport à utiliser

# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      async: '%env(MESSENGER_TRANSPORT_DSN)%'
    routing:
      'GeolocatorBundle\Message\GeolocateMessage': async
```

## Utilisation

Lorsque le mode asynchrone est activé, les requêtes de géolocalisation sont automatiquement déléguées à des workers Messenger :

1. L'application envoie un message `GeolocateMessage` via le bus Messenger
2. Le worker consomme le message et effectue la géolocalisation
3. Le résultat est stocké dans le cache configuré (Redis recommandé en production)
4. Les requêtes suivantes utilisent directement le cache, évitant des appels API répétés

### Exemple de code

```php
use GeolocatorBundle\Service\AsyncGeolocator;
use GeolocatorBundle\Service\GeolocationCache;

class MyController
{
    public function someAction(
        AsyncGeolocator $asyncGeolocator,
        GeolocationCache $cache,
        string $clientIp
    ) {
        // Tenter une géolocalisation asynchrone (renvoie les données du cache si disponibles)
        $geoData = $asyncGeolocator->geolocate($clientIp);

        if ($geoData === null) {
            // Les données ne sont pas encore disponibles (traitement asynchrone en cours)
            // Vous pouvez soit:
            // 1. Attendre le résultat (non recommandé)
            // 2. Utiliser une valeur par défaut
            // 3. Rediriger vers une page d'attente
            return $this->render('waiting.html.twig');
        }

        // Utiliser les données de géolocalisation
        $country = $geoData['country'] ?? 'unknown';
        // ...
    }
}
```

## Exécution des Workers

Pour traiter les messages asynchrones, vous devez exécuter au moins un worker Messenger :

```bash
# Démarrer un worker
bin/console messenger:consume async --limit=10

# En production (avec supervisord)
bin/console messenger:consume async --limit=200 --memory-limit=128M
```

## Avantages du Mode Asynchrone

1. **Performance** : Les requêtes de géolocalisation n'affectent pas le temps de réponse de l'application
2. **Résilience** : En cas de panne d'un fournisseur de géolocalisation, l'application reste réactive
3. **Mise en cache efficace** : Les résultats sont mis en cache pour toutes les requêtes futures
4. **Tolérance aux pannes** : Les messages peuvent être réessayés automatiquement

## Considérations

- Utilisez **Redis** comme backend de cache pour un partage efficace entre workers
- Configurez un **délai d'expiration** approprié pour les données de géolocalisation (les adresses IP peuvent changer de localisation)
- Prévoyez une **stratégie de fallback** pour les cas où les données ne sont pas immédiatement disponibles
