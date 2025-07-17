# Redis Configuration for GeolocatorBundle

## Redis as Message Transport

To use Redis as a message transport for asynchronous geolocation processing:

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      geolocator_async:
        dsn: '%env(REDIS_DSN)%'
        options:
          stream_max_entries: 1000
          timeout_wait: 100
          auto_setup: true
    routing:
      'GeolocatorBundle\Message\GeolocationMessage': geolocator_async
```

## Redis as Ban Storage

You can also use Redis as a storage backend for IP bans:

```yaml
# config/packages/geolocator.yaml
geolocator:
  storage:
    type: redis
    options:
      dsn: '%env(REDIS_DSN)%'
      prefix: 'geolocator:'
      ttl: 86400  # 24 hours
```

## Complete Example with Environment Variables

```yaml
# .env
REDIS_DSN=redis://localhost:6379/0

# config/packages/geolocator.yaml
geolocator:
  messenger_enabled: true
  redis_messenger_enabled: true
  storage:
    type: redis
    options:
      dsn: '%env(REDIS_DSN)%'

# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      geolocator_async:
        dsn: '%env(REDIS_DSN)%'
    routing:
      'GeolocatorBundle\Message\GeolocationMessage': geolocator_async
```

## Redis Cluster Configuration

For high-availability Redis:

```yaml
# .env
REDIS_DSN=redis://localhost:7000,redis://localhost:7001,redis://localhost:7002

# config/packages/geolocator.yaml
geolocator:
  storage:
    type: redis
    options:
      dsn: '%env(REDIS_DSN)%'
      cluster: true
```
