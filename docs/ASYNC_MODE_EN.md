# Async Mode

GeolocatorBundle supports asynchronous processing of geolocation requests via Symfony Messenger. This improves your application performance by delegating intensive geographic processing to asynchronous workers.

## Configuration

To enable asynchronous mode, you need to configure RabbitMQ (or another transport compatible with Symfony Messenger).

### 1. Install dependencies

```bash
composer require symfony/messenger symfony/amqp-messenger
```

### 2. Configure transport in `.env`

```dotenv
# .env or .env.local
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

### 3. Enable async mode in configuration

```yaml
# config/packages/geolocator.yaml
geolocator:
  rabbit_enabled: true  # Enable async mode
  messenger_transport: 'async'  # Transport name to use

# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      async: '%env(MESSENGER_TRANSPORT_DSN)%'
    routing:
      'GeolocatorBundle\Message\GeolocateMessage': async
```

## Usage

When async mode is enabled, geolocation requests are automatically delegated to Messenger workers:

1. The application sends a `GeolocateMessage` via the Messenger bus
2. The worker consumes the message and performs geolocation
3. The result is stored in the configured cache (Redis recommended in production)
4. Subsequent requests directly use the cache, avoiding repeated API calls

### Code Example

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
        // Try async geolocation (returns cache data if available)
        $geoData = $asyncGeolocator->geolocate($clientIp);

        if ($geoData === null) {
            // Data not yet available (async processing in progress)
            // You can either:
            // 1. Wait for the result (not recommended)
            // 2. Use a default value
            // 3. Redirect to a waiting page
            return $this->render('waiting.html.twig');
        }

        // Use geolocation data
        $country = $geoData['country'] ?? 'unknown';
        // ...
    }
}
```

## Running Workers

To process async messages, you must run at least one Messenger worker:

```bash
# Start a worker
bin/console messenger:consume async --limit=10

# In production (with supervisord)
bin/console messenger:consume async --limit=200 --memory-limit=128M
```

## Benefits of Async Mode

1. **Performance**: Geolocation requests don't affect application response time
2. **Resilience**: If a geolocation provider fails, the application remains responsive
3. **Efficient caching**: Results are cached for all future requests
4. **Fault tolerance**: Messages can be automatically retried

## Considerations

- Use **Redis** as cache backend for efficient sharing between workers
- Configure appropriate **expiration time** for geolocation data (IP addresses can change location)
- Plan a **fallback strategy** for cases where data is not immediately available
