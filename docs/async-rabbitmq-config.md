# RabbitMQ Configuration for GeolocatorBundle

## RabbitMQ as Message Transport

To use RabbitMQ as a message transport for asynchronous geolocation processing:

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      geolocator_async:
        dsn: '%env(RABBITMQ_DSN)%'
        options:
          exchange:
            name: geolocator
            type: direct
          queues:
            geolocator_queue:
              binding_keys: [geolocator]
          auto_setup: true
    routing:
      'GeolocatorBundle\Message\GeolocationMessage': geolocator_async
```

## Complete Example with Environment Variables

```yaml
# .env
RABBITMQ_DSN=amqp://guest:guest@localhost:5672/%2f/messages

# config/packages/geolocator.yaml
geolocator:
  messenger_enabled: true
  rabbit_enabled: true

# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      geolocator_async:
        dsn: '%env(RABBITMQ_DSN)%'
    routing:
      'GeolocatorBundle\Message\GeolocationMessage': geolocator_async
```

## RabbitMQ Worker Deployment

In production, you should run RabbitMQ workers using Supervisor:

```ini
# /etc/supervisor/conf.d/geolocator_worker.conf
[program:geolocator_worker]
command=php /path/to/your/app/bin/console messenger:consume geolocator_async --time-limit=3600
user=www-data
numprocs=2
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
```

## Scaling with Multiple Workers

You can scale the geolocation processing by adjusting the number of workers:

```ini
# Increase numprocs for more workers
numprocs=4
```

## RabbitMQ High Availability

For high-availability setups:

```yaml
# .env
RABBITMQ_DSN=amqp://user:pass@rabbitmq1:5672/%2f/messages amqp://user:pass@rabbitmq2:5672/%2f/messages

# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      geolocator_async:
        dsn: '%env(RABBITMQ_DSN)%'
        options:
          connection_timeout: 3
          heartbeat: 30
```
