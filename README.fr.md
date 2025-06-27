# GeolocatorBundle

# Français

### 📦 Bundle Symfony 7.3 (PHP 8.3)

Filtrage d’accès basé sur la géolocalisation IP, configuration flexible, extensible.

### Sommaire

- [Installation](#installation)
- [Configuration](#configuration)
- [Services & Composants](#services--composants)
- [Usage](#usage)
- [Commandes CLI](#commandes-cli)
- [Dashboard Admin](#dashboard-admin)
- [Tests & Doc Interactive](#tests--doc-interactive)
- [Sécurité & Performance](#sécurité--performance)
- [Extensibilité](#extensibilité)
- [Packaging](#packaging)

---

### Installation

Voir [docs/INSTALLATION.md](docs/INSTALLATION.md)

### Configuration

#### Mode simple par défaut
```yaml
# config/packages/framework.yaml
framework:
  cache:
    app: cache.adapter.filesystem  # Cache sur filesystem

# config/packages/geolocator.yaml
geolocator:
  enabled: true
  redis_enabled: false      # Désactive Redis (utilise filesystem)
  rabbit_enabled: false     # Désactive RabbitMQ (mode synchrone)
  cache_pool: 'cache.app'
  redirect_route: 'app_blocked'
  use_custom_blocked_page: true
  simulate: false
  ban_duration: '3 hours'
  ping_threshold: 10
  # Filtrage...
```

#### Activer Redis ou RabbitMQ
```yaml
# Pour Redis
geolocator:
  redis_enabled: true

framework:
  cache:
    app: cache.adapter.redis

# Pour RabbitMQ (mode async)
geolocator:
  rabbit_enabled: true

framework:
  messenger:
    transports:
      async: '%env(MESSENGER_TRANSPORT_DSN)%'
    routing:
      'Xorg\GeolocatorBundle\Message\GeolocateMessage': async
```

---

### Services & Composants

| Service            | Rôle                                          |
| ------------------ | --------------------------------------------- |
| `IpResolver`       | Résolution automatique de l’IP client (XFF)   |
| `GeolocationCache` | Cache PSR-6 autour des providers IP           |
| `ProviderManager`  | Round‑robin des providers configurés          |
| `BanManager`       | Gestion des bans en session                   |
| `BotDetector`      | Détection de bots via User-Agent              |
| `RateLimiter`      | Protection flood/ping via Symfony RateLimiter |
| `WebhookNotifier`  | Envoi de webhooks sur blocages                |

### Usage

```php
use Xorg\GeolocatorBundle\Attribute\GeoFilter;

#[GeoFilter(
    blockedCountries: ['CN','RU'],
    requireNonVPN: true,
    simulate: false
)]
public function index(): Response
{
    // ...
}
```

Options : `blockedIps`, `blockedRanges`, `allowedRanges`, `blockedCountries`, `blockedContinents`, `blockedAsns`, `blockedIsps`, `requireNonVPN`, `pingThreshold`, `simulate`, `forceProvider`

### Commandes CLI

```bash
php bin/console xorg:geolocator:ban:list
php bin/console xorg:geolocator:ban:add <ip> [duration]
php bin/console xorg:geolocator:ban:remove <ip>
php bin/console xorg:geolocator:check-dsn
php bin/console xorg:geolocator:export-firewall <format>
```

### Dashboard Admin

Route : `/admin/geolocator`

- Liste paginée des IP bannies (raison, expiration)
- Actions : débannir, ajuster durée, export CSV/XLSX

### Tests & Doc Interactive

- Tests unitaires (PestPHP) et intégration HTTP (WebTestCase/Panther)
- Swagger UI à `/api/docs` (NelmioApiDocBundle)

### Sécurité & Performance

- Validation des DSN (prévenir SSRF)
- Timeout et retry, appels asynchrones optionnels
- Cache partagé (PSR-6) pour réduire la latence

### Extensibilité

Le système utilise l’interface `FilterInterface` et le tag `xorg.geofilter.filter`.Pour plus d’infos, voir [docs/EXTENSIBILITY.md](docs/EXTENSIBILITY.md).

### Packaging

- Publication Packagist
- Installation via Composer + Symfony Flex

*© 2025 XorgGeolocatorBundle*
