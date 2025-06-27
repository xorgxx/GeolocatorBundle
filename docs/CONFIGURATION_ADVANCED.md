# Configuration Avancée

Ce document décrit les options de configuration avancée via `config/packages/geolocator.yaml`.

## Cache Redis

Pour utiliser Redis comme cache PSR-6 :

```yaml
framework:
  cache:
    app: cache.adapter.redis

xorg_geolocator:
  redis_enabled: true
  cache_pool: 'cache.app'
```

## Traitement Asynchrone (RabbitMQ)

Pour activer le traitement asynchrone via Symfony Messenger :

```yaml
framework:
  messenger:
    transports:
      geolocator: '%env(MESSENGER_TRANSPORT_DSN)%'
    routing:
      'GeolocatorBundle\Message\GeolocateMessage': geolocator

xorg_geolocator:
  rabbit_enabled: true
  messenger_transport: 'geolocator'
```

## Paramètres

- `redis_enabled`: boolean (defaut false)  
- `rabbit_enabled`: boolean (defaut false)  
- `cache_pool`: string, nom du pool PSR-6  
- `messenger_transport`: string, nom du transport Messenger  
