# Guide de validation du GeolocatorBundle

Ce guide vous aide à vérifier que votre installation du GeolocatorBundle est fonctionnelle et correctement configurée.

## 1. Implémentation des filtres

Le GeolocatorBundle inclut plusieurs filtres que vous pouvez utiliser :

- **CountryFilter** : Filtre par pays
- **CrawlerFilter** : Détection des robots d'indexation
- **VpnDetector** : Détection de VPN/proxy

Vous pouvez étendre ces filtres ou créer les vôtres en implémentant l'interface `App\Bundle\GeolocatorBundle\Filter\FilterInterface`.

## 2. Configuration réelle

### Vérification de la configuration

1. Assurez-vous que le fichier `config/packages/geolocator.yaml` est correctement chargé :

```yaml
geolocator:
  enabled: true
  # Configuration supplémentaire...
```

2. Pour les environnements de production, activez Redis ou RabbitMQ :

```yaml
geolocator:
  redis_enabled: true  # Ou false si vous n'utilisez pas Redis
  rabbit_enabled: true  # Ou false si vous n'utilisez pas RabbitMQ
```

3. Pour les environnements de test, désactivez Redis et RabbitMQ pour rester en mode synchrone/filesystem :

```yaml
# config/packages/test/geolocator.yaml
geolocator:
  redis_enabled: false
  rabbit_enabled: false
  simulate: true  # Activer le mode simulation en test
```

### Variables d'environnement

Assurez-vous que les variables d'environnement nécessaires sont définies dans `.env.local` :

```
# Fournisseurs de géolocalisation
IPQUALITYSCORE_APIKEY=votre_clé_api

# Configuration Redis
REDIS_DSN=redis://localhost:6379

# Configuration RabbitMQ
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages

# Configuration du proxy
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
```

## 3. Composer & autoload

1. Exécutez `composer dump-autoload` pour prendre en compte les namespaces.

2. Vérifiez que le bundle apparaît dans `config/bundles.php` :

```php
return [
    // ...
    App\Bundle\GeolocatorBundle\GeolocatorBundle::class => ['all' => true],
];
```

## 4. Tests de base

### Tests unitaires

Lancez les tests unitaires :

```bash
vendor/bin/pest
```

### Tests d'intégration

Créez un WebTestCase pour tester les fonctionnalités principales :

```php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GeolocatorTest extends WebTestCase
{
    public function testBlockedCountry(): void
    {
        $client = static::createClient();
        // Simuler une IP d'un pays bloqué
        $client->setServerParameter('HTTP_X_FORWARDED_FOR', '8.8.8.8');

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAllowedCountry(): void
    {
        $client = static::createClient();
        // Simuler une IP d'un pays autorisé
        $client->setServerParameter('HTTP_X_FORWARDED_FOR', '91.198.174.192');

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    // Autres tests...
}
```

## 5. Exécution manuelle

### Démarrer le serveur

```bash
bin/console server:run
```

### Tester les routes

1. Page de debug (accessible uniquement en environnement de développement) :

```
http://localhost:8000/__geo/debug
```

2. Page d'accueil :

```
http://localhost:8000/
```

3. Dashboard admin (si configuré) :

```
http://localhost:8000/admin/geolocator
```

### Tester avec différentes IPs

Utilisez l'en-tête `X-Forwarded-For` pour simuler différentes IPs :

```bash
curl -H "X-Forwarded-For: 8.8.8.8" http://localhost:8000/
```

## 6. Vérification du profiler

En environnement de développement, vérifiez que le profiler Symfony affiche les informations de géolocalisation :

1. Accédez à n'importe quelle page de votre application
2. Cliquez sur l'icône du profiler Symfony
3. Vérifiez qu'un onglet "Geolocator" est présent
4. Vérifiez que les informations de géolocalisation sont correctement affichées

## 7. Vérification des logs

Vérifiez que les logs sont correctement générés :

```bash
tail -f var/log/dev.log | grep geolocator
```

## 8. Cas d'usage courants

### Utiliser l'attribut GeoFilter

```php
use App\Bundle\GeolocatorBundle\Attribute\GeoFilter;

#[Route('/', name: 'app_home')]
#[GeoFilter(
    blockedCountries: ['RU', 'CN'],
    requireNonVPN: true
)]
public function index(): Response
{
    // ...
}
```

### Utiliser le service directement

```php
use App\Bundle\GeolocatorBundle\Service\GeolocatorService;

public function someAction(Request $request, GeolocatorService $geolocator)
{
    $result = $geolocator->processRequest($request);

    if ($result->isBanned()) {
        // L'utilisateur est banni
        $reason = $result->getReason();
        // ...
    } else {
        // L'utilisateur est autorisé
        $geoLocation = $result->getGeoLocation();
        $country = $geoLocation->getCountryCode();
        // ...
    }
}
```

## Résolution des problèmes courants

### Le bundle n'est pas reconnu

- Vérifiez que le bundle est correctement enregistré dans `config/bundles.php`
- Exécutez `composer dump-autoload`
- Vérifiez que le namespace est correct dans tous les fichiers

### Erreurs de géolocalisation

- Vérifiez que les DSN des fournisseurs sont corrects
- Vérifiez que les clés API sont valides
- Vérifiez les logs pour plus d'informations
- Si aucun fournisseur n'est disponible, le bundle activera automatiquement un mode de secours avec un provider local

### Mode de secours automatique

Le GeolocatorBundle inclut un mécanisme de secours qui s'active automatiquement si aucun fournisseur externe n'est configuré ou disponible. Dans ce mode :

- Un provider local est utilisé pour fournir des informations de base
- Les IPs locales/privées sont détectées et assignées au pays "FR" par défaut
- Un message d'avertissement est enregistré dans les logs

Vous pouvez aussi activer manuellement ce mode :

```yaml
geolocator:
  provider_fallback_mode: true
```

### Performance lente

- Activez Redis pour le cache
- Configurez des timeouts plus courts pour les fournisseurs
- Utilisez RabbitMQ pour traiter les géolocalisations de manière asynchrone
