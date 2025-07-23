# GeoFilter Attribute

The `GeoFilter` attribute allows you to configure geographic and IP filtering rules directly on your Symfony controllers or controller methods.

## Basic Usage

```php
<?php

namespace App\Controller;

use GeolocatorBundle\Attribute\GeoFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController
{
    #[Route('/api/secure', name: 'api_secure')]
    #[GeoFilter(
        allowedCountries: ['FR', 'BE', 'CH'], 
        blockedCountries: ['RU', 'CN'],
        allowedIps: ['192.168.1.0/24'], 
        blockedIps: ['1.2.3.4']
    )]
    public function secureEndpoint(): Response
    {
        // This method is only accessible from FR, BE or CH,
        // but not from RU or CN
        // Additionally, IPs in the range 192.168.1.0/24 are always allowed,
        // but IP 1.2.3.4 is always blocked

        return new Response('Secure content');
    }
}
```

## Available Options

### Country Filtering

```php
#[GeoFilter(
    allowedCountries: ['FR', 'BE'], // Only these countries are allowed
    blockedCountries: ['RU', 'CN']   // These countries are always blocked
)]
```

If `allowedCountries` is defined, only the listed countries are allowed.
If `blockedCountries` is defined, these countries are always blocked, even if they are in `allowedCountries`.

### IP Filtering

```php
#[GeoFilter(
    allowedIps: ['127.0.0.1', '192.168.1.0/24', '10.0.0.*'], // Allowed IPs/ranges
    blockedIps: ['1.2.3.4', '5.6.7.0/24'],                     // Blocked IPs/ranges
    allowIpsExclusive: true                                      // Exclusive whitelist mode
)]
```

IP filtering supports multiple formats:
- Exact IP: `127.0.0.1`
- CIDR range: `192.168.1.0/24`
- Wildcard: `10.0.0.*`

If `allowIpsExclusive` is `true`, only IPs in `allowedIps` are allowed, all others are blocked.

### Other Options

```php
#[GeoFilter(
    blockVpn: true,            // Block VPNs, proxies and Tor (default)
    blockCrawlers: true,        // Block web crawlers (disabled by default)
    redirectUrl: '/blocked'     // Custom redirect URL when blocked
)]
```

## Class-level Application

You can apply the attribute to an entire class to protect all its methods:

```php
#[GeoFilter(allowedCountries: ['FR', 'BE'])]
class SecureController
{
    // All methods in this controller are only accessible from France and Belgium
}
```

## Method-level Override

If an attribute is defined on both the class and a method, the method attribute takes precedence:

```php
#[GeoFilter(allowedCountries: ['FR', 'BE'])]
class SecureController
{
    #[GeoFilter(allowedCountries: ['FR', 'BE', 'CH', 'LU'])]
    public function lessRestrictedAction()
    {
        // Accessible from FR, BE, CH and LU
    }

    public function defaultAction()
    {
        // Accessible only from FR and BE (class rule)
    }
}
```

## Rule Priority Order

Rules are evaluated in this order:

1. IP filtering (whitelist has priority)
2. Country filtering
3. VPN detection
4. Crawler filtering

This means that an IP in the allow list can access even if the country is blocked.

## Redirection

When blocked, the user is redirected to:

1. The URL specified in the attribute's `redirectUrl`
2. If not defined, the global URL configured in `geolocator.redirect_on_ban`
3. If not defined, `/blocked` by default

## Usage Examples

### Secured API for Europe Only

```php
#[GeoFilter(allowedCountries: ['FR', 'DE', 'IT', 'ES', 'BE', 'NL', 'LU', 'CH', 'AT'])]
class ApiController
```

### Content Reserved for Certain IPs

```php
#[GeoFilter(allowedIps: ['10.0.0.0/8', '192.168.0.0/16'], allowIpsExclusive: true)]
public function intranetAction()
```

### Block VPNs but Allow Certain IPs

```php
#[GeoFilter(blockVpn: true, allowedIps: ['203.0.113.0/24'])]
public function secureButAllowCertainProxies()
```

### Highly Secured Admin Area

```php
#[GeoFilter(
    allowedCountries: ['FR'],
    allowedIps: ['10.0.0.0/8'],
    blockVpn: true,
    blockCrawlers: true,
    redirectUrl: '/admin/access-denied'
)]
class AdminController
```
