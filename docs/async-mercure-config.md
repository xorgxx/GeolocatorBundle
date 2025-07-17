# Mercure Configuration for GeolocatorBundle

## Mercure for Real-Time Notifications

The GeolocatorBundle can use Mercure to send real-time notifications when an IP is banned or when a suspicious activity is detected.

```yaml
# config/packages/mercure.yaml
mercure:
  hubs:
    default:
      url: '%env(MERCURE_URL)%'
      public_url: '%env(MERCURE_PUBLIC_URL)%'
      jwt:
        secret: '%env(MERCURE_JWT_SECRET)%'
        publish: '*'
```

## GeolocatorBundle Configuration

```yaml
# config/packages/geolocator.yaml
geolocator:
  mercure_enabled: true
  mercure:
    # Topics for different notification types
    topics:
      ban: 'geolocator/ban'
      vpn_detection: 'geolocator/vpn'
      country_filter: 'geolocator/country'
```

## Complete Example with Environment Variables

```yaml
# .env
MERCURE_URL=https://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=https://mercure/.well-known/mercure
MERCURE_JWT_SECRET=!ChangeThisMercureHubJWTSecretKey!

# config/packages/geolocator.yaml
geolocator:
  mercure_enabled: true
```

## JavaScript Client Example

To receive notifications in a browser:

```javascript
// assets/js/geolocator-notifications.js
document.addEventListener('DOMContentLoaded', () => {
  const url = new URL(document.querySelector('link[rel="mercure"]').href);

  // Subscribe to ban events
  url.searchParams.append('topic', 'geolocator/ban');

  const eventSource = new EventSource(url);

  eventSource.onmessage = event => {
    const data = JSON.parse(event.data);
    console.log('Ban event received:', data);

    // Show notification to admin
    if (data.ip && data.reason) {
      showNotification(`IP ${data.ip} banned: ${data.reason}`);
    }
  };
});

function showNotification(message) {
  // Your notification logic here
  const notification = document.createElement('div');
  notification.className = 'geolocator-notification';
  notification.textContent = message;
  document.body.appendChild(notification);

  setTimeout(() => {
    notification.remove();
  }, 5000);
}
```

## Security Considerations

When using Mercure, ensure that:

1. Only authenticated admins can subscribe to ban topics
2. The JWT secrets are properly secured
3. Sensitive data is properly sanitized before publishing

See the [Mercure security documentation](https://mercure.rocks/docs/hub/security) for more details.
