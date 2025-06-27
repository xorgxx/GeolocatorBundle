# Advanced Configuration

This document covers advanced settings in `config/packages/geolocator.yaml`.

## Redis Cache

To use Redis as PSR-6 cache:

```yaml
framework:
  cache:
    app: cache.adapter.redis

geolocator:
  redis_enabled: true
  cache_pool: 'cache.app'
```

## Asynchronous Processing (RabbitMQ)

Enable asynchronous processing with Symfony Messenger:

```yaml
framework:
  messenger:
    transports:
      geolocator: '%env(MESSENGER_TRANSPORT_DSN)%'
    routing:
      'GeolocatorBundle\Message\GeolocateMessage': geolocator

geolocator:
  rabbit_enabled: true
  messenger_transport: 'geolocator'
```

## Parameters

- `redis_enabled`: boolean (default false)  
- `rabbit_enabled`: boolean (default false)  
- `cache_pool`: string, PSR-6 cache pool name  
- `messenger_transport`: string, Messenger transport name  
