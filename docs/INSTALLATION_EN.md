# GeolocatorBundle Installation

## Prerequisites

- PHP 8.2 or higher
- Symfony 6.0 or higher

## Installation via Composer

```bash
composer require app/geolocator-bundle
```

## Manual Configuration (if Flex is not enabled)

1. Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    App\Bundle\GeolocatorBundle\GeolocatorBundle::class => ['all' => true],
];
```

2. Create the configuration file in `config/packages/geolocator.yaml`:

```yaml
geolocator:
    enabled: true
    # Additional configuration...
```

3. Configure routes in `config/routes/geolocator.yaml`:

```yaml
geolocator:
    resource: '@App\Bundle\GeolocatorBundle/Controller/'
    type: annotation
```

4. Configure your environment variables in `.env.local`:

```
# API key for IPQualityScore (if used)
IPQUALITYSCORE_APIKEY=your_api_key

# Redis (if enabled)
REDIS_DSN=redis://localhost

# RabbitMQ (if enabled)
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@localhost:5672/%2f/messages
```

## Verifying Installation

1. Check that the bundle is properly recognized:

```bash
bin/console debug:container --tag=geolocator.provider
```

2. Test the provider verification command:

```bash
bin/console geolocator:test 8.8.8.8
```

3. Access the debug page (if available):

```
http://your-site.local/__geo/debug
```

## Advanced Configuration

Consult the complete documentation for advanced configuration options:

- [Advanced Configuration](CONFIGURATION_EN.md)
- [Extensibility](EXTENSIBILITY_EN.md)
