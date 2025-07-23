# Symfony Profiler Integration

GeolocatorBundle seamlessly integrates with the Symfony Profiler (Web Debug Toolbar) to provide detailed information about visitor geolocation during development.

## Features

The profiler integration allows you to:

1. **Quickly visualize** geolocation information in the toolbar
2. **Identify blocked IPs** with a visual indicator
3. **Detect VPNs and crawlers** with specific alerts
4. **View complete details** in the profiler panel

## Activation

The profiler integration is automatically enabled in the development environment (`dev`) when the `symfony/web-profiler-bundle` component is installed:

```bash
composer require --dev symfony/web-profiler-bundle
```

The service is already configured in `services.yaml`:

```yaml
# src/Resources/config/services.yaml
geolocator.data_collector:
    class: GeolocatorBundle\DataCollector\GeolocatorDataCollector
    arguments:
        - '@geolocator.service'
    tags:
        - { name: data_collector, template: '@Geolocator/data_collector/template.html.twig', id: geolocator, priority: 255 }
```

No additional configuration is required.

## Usage

### Toolbar

The toolbar displays:

- **The country code** of the detected IP
- **A colored indicator**: 
  - Red: Blocked IP
  - Yellow: VPN/Proxy detected
  - Normal: No issues detected

Hovering over the icon displays additional information in a tooltip:

- IP address
- Full country name
- City
- Status (blocked or not)
- Whether a VPN/Proxy is detected
- Whether a crawler is detected
- Whether simulation mode is active

### Detailed Panel

Clicking on the icon in the toolbar takes you to the detailed panel which displays:

1. **General Information**:
   - IP, country, city, coordinates
   - Provider used for geolocation

2. **Statuses**:
   - Blocked/allowed
   - VPN/Proxy detected
   - Crawler detected
   - Simulation mode active/inactive
   - Asynchronous mode available/unavailable

3. **Ban Information** (if IP is blocked):
   - Reason for blocking
   - Start date
   - Duration/expiration

## Development Benefits

- **Simplified debugging**: Quickly visualize geolocation information without adding debug code
- **Rule testing**: Test your country filtering and VPN detection rules in real-time
- **Simulation mode verification**: Confirm that simulation mode is working correctly
- **Ban monitoring**: Track blocked IPs and associated reasons

## Usage Example

1. Access your application in the development environment
2. Observe the geolocation icon in the toolbar
3. Hover over it to see basic information
4. Click to access the detailed panel
5. Test different rules and observe changes in the profiler

## Screenshot

![Geolocator Profiler](profiler-screenshot.png)

## Notes

- This feature is automatically disabled in the production environment
- To simulate different countries, you can use VPNs or manually modify geolocation information by extending the providers
