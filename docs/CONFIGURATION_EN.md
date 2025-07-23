# GeolocatorBundle Configuration

## Basic Configuration

The bundle configuration is done in the `config/packages/geolocator.yaml` file. Here is a complete configuration example:

```yaml
geolocator:
  enabled: true                # Enable/disable the entire bundle
  event_bridge_service: null   # External service to delegate events

  ip_filter_flags:             # HTTP headers to check for client IP
      - 'X-Forwarded-For'
      - 'Client-Ip'

  providers:                   # Geolocation providers configuration
    default: 'ipapi'           # Main provider
    list:                      # Available providers list
      ipapi:
        dsn: 'https://ipapi.co/{ip}/json/'
      ipwhois:
        dsn: 'https://ipwhois.app/json/{ip}'
      ipqualityscore:
        dsn: 'https://ipqualityscore.com/api/json/ip/{apikey}/{ip}'
        apikey: '%env(IPQUALITYSCORE_APIKEY)%'
    fallback: [ 'ipwhois', 'ipapi' ]  # Fallback providers

  storage:                     # Storage configuration
    type: 'json'               # 'memory', 'json', 'redis'
    file: '%kernel.project_dir%/var/bans.json'
    redis_dsn: 'redis://localhost'

  bans:                        # Ban configuration
    max_attempts: 10           # Number of attempts before ban
    ttl: 3600                  # Duration in seconds
    ban_duration: '1 hour'     # Format: '1 hour', '30 minutes', '1 day'
    permanent_countries: [ 'RU', 'CN' ]  # Countries to ban permanently

  country_filters:             # Country filtering
    allow: [ 'FR', 'BE' ]      # Allow only these countries
    block: [ 'RU', 'CN' ]      # Block these countries

  vpn_detection:               # VPN detection
    enabled: true
    provider: 'ipqualityscore'  # Provider to use
    allowed_ips: []            # IPs allowed even if VPN

  crawler_filter:              # Crawler filtering
    enabled: true
    allow_known: false         # Allow known crawlers (Google, Bing...)

  redirect_on_ban: '/banned'   # Redirection URL if banned

  log_channel: 'geolocator'    # Log channel
  log_level: 'warning'         # Minimum log level

  profiler:                    # Profiler configuration
    enabled: true

  simulate: false              # Simulation mode (does not block)
```

## Provider Configuration

### Available Providers

The bundle supports several geolocation providers:

- **ipapi**: Free provider with limitations (1000 requests/day)
- **ipwhois**: Free provider with limitations (10000 requests/day)
- **ipqualityscore**: Paid provider with advanced VPN/Proxy detection (requires API key)

### Adding a Custom Provider

You can add your own provider by implementing the `App\Bundle\GeolocatorBundle\Provider\ProviderInterface` and registering it as a service with the `geolocator.provider` tag.

```yaml
services:
    app.geolocator.provider.custom:
        class: App\Provider\CustomProvider
        arguments:
            $httpClient: '@http_client'
            $config: { dsn: 'https://custom-api.com/{ip}' }
        tags:
            - { name: 'geolocator.provider', alias: 'custom' }
```

## Storage Configuration

### Available Storage Types

- **memory**: In-memory storage (non-persistent, only for testing)
- **json**: Storage in a JSON file
- **redis**: Storage in Redis (recommended for production)

### Redis Configuration

To use Redis, you need to configure the Redis DSN:

```yaml
geolocator:
  storage:
    type: 'redis'
    redis_dsn: '%env(REDIS_DSN)%'
```

And define the environment variable:

```
REDIS_DSN=redis://localhost:6379
```

## Ban Configuration

### Ban Duration

You can configure the duration of temporary bans:

```yaml
geolocator:
  bans:
    ban_duration: '1 day'
```

Accepted formats: '30 seconds', '5 minutes', '2 hours', '1 day', '1 week', '1 month'

### Permanent Bans

You can configure some countries to be permanently banned:

```yaml
geolocator:
  bans:
    permanent_countries: ['RU', 'CN', 'KP']
```

## Filter Configuration

### Country Filtering

You can filter requests by country using allow or block lists:

```yaml
geolocator:
  country_filters:
    allow: ['FR', 'BE', 'CH', 'LU']  # Only these countries are allowed
    block: ['RU', 'CN', 'KP']        # These countries are blocked
```

If both lists are defined, the allow list takes precedence.

### VPN Detection

You can enable VPN/Proxy detection:

```yaml
geolocator:
  vpn_detection:
    enabled: true
    provider: 'ipqualityscore'  # Provider that supports VPN detection
    allowed_ips: ['123.45.67.89']  # IPs exempted from VPN detection
```

### Crawler Filtering

You can configure crawler filtering:

```yaml
geolocator:
  crawler_filter:
    enabled: true
    allow_known: true  # Allow known crawlers (Google, Bing...)
```

## Simulation Mode Configuration

You can enable simulation mode to test your rules without actually blocking users:

```yaml
geolocator:
  simulate: true
```

In simulation mode, ban actions are logged but not applied. This allows you to verify your rules in production without risk.

## Log Configuration

```yaml
geolocator:
  log_channel: 'geolocator'
  log_level: 'warning'  # Options: debug, info, warning, error, critical
```

## Symfony Profiler Configuration

The bundle integrates with Symfony Profiler for easier debugging:

```yaml
geolocator:
  profiler:
    enabled: true  # Enabled in development, disabled in production
```

## Advanced Configuration

### Event Bridge Service

You can configure an external service to delegate events:

```yaml
geolocator:
  event_bridge_service: 'app.event_bridge'
```

This allows you to integrate the bundle with external systems like AWS EventBridge, RabbitMQ, or other messaging systems.

### HTTP Headers for IP Resolution

You can configure which HTTP headers to check to determine the client IP:

```yaml
geolocator:
  ip_filter_flags:
    - 'X-Forwarded-For'
    - 'X-Real-IP'
    - 'Client-Ip'
```

Headers are checked in the specified order. If no header contains a valid IP, the Symfony request IP is used.
