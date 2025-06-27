# English

### 📦 Symfony Bundle 7.3 (PHP 8.3)

Access filtering based on IP geolocation, flexible configuration, extensible.

### Table of Contents

- [Installation](#installation-1)
- [Configuration](#configuration-1)
- [Services & Components](#services--components)
- [Usage](#usage-1)
- [CLI Commands](#cli-commands)
- [Admin Dashboard](#admin-dashboard)
- [Tests & Interactive Docs](#tests--interactive-docs)
- [Security & Performance](#security--performance)
- [Extensibility](#extensibility)
- [Packaging](#packaging-1)

---

### Installation

See [docs/INSTALLATION_EN.md](docs/INSTALLATION_EN.md)

### Configuration

- `.env` / `.env.local`
- `config/packages/framework.yaml`
- `config/packages/geolocator.yaml`

### Services & Components

| Service            | Role                                   |
| ------------------ | -------------------------------------- |
| `IpResolver`       | Client IP resolution (X-Forwarded-For) |
| `GeolocationCache` | PSR-6 cache for geolocation            |
| `ProviderManager`  | Round-robin of configured providers    |
| `BanManager`       | Ban management in session              |
| `BotDetector`      | Bot detection via User-Agent           |
| `RateLimiter`      | Flood/ping protection                  |
| `WebhookNotifier`  | Webhook notifications on blocks        |

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

Options: `blockedIps`, `blockedRanges`, `allowedRanges`, `blockedCountries`, `blockedContinents`, `blockedAsns`, `blockedIsps`, `requireNonVPN`, `pingThreshold`, `simulate`, `forceProvider`

### CLI Commands

```bash
php bin/console xorg:geolocator:ban:list
php bin/console xorg:geolocator:ban:add <ip> [duration]
php bin/console xorg:geolocator:ban:remove <ip>
php bin/console xorg:geolocator:check-dsn
php bin/console xorg:geolocator:export-firewall <format>
```

### Admin Dashboard

**Route**: `/admin/geolocator`

- Paginated list of banned IPs (reason, expiry)
- Actions: unban, adjust duration, export CSV/XLSX

### Tests & Interactive Docs

- Unit tests (PestPHP) and HTTP integration tests (WebTestCase/Panther)
- Swagger UI at `/api/docs` (NelmioApiDocBundle)

### Security & Performance

- DSN validation (prevent SSRF)
- Timeout and retry, optional async calls
- Shared cache (PSR-6) to reduce latency

### Extensibility

System uses `FilterInterface` and the `xorg.geofilter.filter` tag.See [docs/EXTENSIBILITY_EN.md](docs/EXTENSIBILITY_EN.md).

### Packaging

- Publish on Packagist
- Install via Composer + Symfony Flex

*© 2025 XorgGeolocatorBundle*