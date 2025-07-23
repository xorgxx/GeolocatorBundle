# Asynchronous Mode for GeolocatorBundle

GeolocatorBundle supports asynchronous processing of geolocation requests via Symfony Messenger, improving the performance and resilience of your application.

## How It Works

The asynchronous mode allows you to:

1. **Improve performance**: Geolocation requests are processed in the background, avoiding increased response times
2. **Handle traffic spikes**: Requests are queued and processed according to system capacity
3. **Enhance resilience**: Even if geolocation services are temporarily unavailable, your application continues to function

## Requirements

To use the asynchronous mode, you must install at least one of the following components:

```bash
# Minimal installation (Messenger)
composer require symfony/messenger

# To use RabbitMQ as transport
composer require symfony/amqp-messenger

# To use Redis as transport
composer require symfony/redis-messenger predis/predis

# For real-time notifications
composer require symfony/mercure-bundle
```

## Configuration

The bundle automatically detects installed components and configures asynchronous mode accordingly. However, you can manually configure it:

```yaml
# config/packages/geolocator.yaml
geolocator:
  # Enable/disable automatic detection of asynchronous services
  auto_detect_services: true

  # Manual transport configuration
  messenger_enabled: true
  rabbit_enabled: false
  redis_messenger_enabled: true
  mercure_enabled: false
```

## Symfony Messenger Configuration

You need to configure Symfony Messenger to process asynchronous messages:

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      # Use the transport of your choice (redis, amqp, doctrine...)
      geolocator_async: '%env(MESSENGER_TRANSPORT_DSN)%'

    routing:
      # Route messages to the appropriate transport
      'GeolocatorBundle\Message\GeolocationMessage': geolocator_async
```

## Usage Examples

### In a Controller

```php
use GeolocatorBundle\Service\GeolocatorService;

class SomeController
{
    public function someAction(Request $request, GeolocatorService $geolocator)
    {
        // Asynchronous usage (will queue the request)
        $geolocator->getGeoLocationFromRequest($request, true);

        // ... your code
    }
}
```

### Configuring the Messenger Worker

To process messages in the background, you must run a Messenger worker:

```bash
# Start a Messenger worker
php bin/console messenger:consume geolocator_async

# In production, with supervisord
# see https://symfony.com/doc/current/messenger.html#deploying-to-production
```

## Simulation Mode and Asynchronous Mode

You can combine simulation mode and asynchronous mode:

```yaml
# config/packages/geolocator.yaml
geolocator:
  simulate: true
  messenger_enabled: true
```

In this case:
- Requests will be sent asynchronously
- The worker will process the requests
- No actual blocking will be performed (simulation)
- Everything will be recorded in the logs

## Monitoring and Debugging

Asynchronous requests are logged with the following information:

```
[info] Geolocation task dispatched asynchronously {"ip":"123.45.67.89"}
[info] Asynchronous geolocation successful {"ip":"123.45.67.89","country":"FR"}
```

You can also use the Symfony Messenger dashboard to monitor the queue:

```bash
php bin/console messenger:failed:show
php bin/console messenger:stats
```

## Failure Handling

If the asynchronous mode fails (Messenger unavailable, dispatch error, etc.), the system will automatically use synchronous mode as a fallback to ensure service continuity.
