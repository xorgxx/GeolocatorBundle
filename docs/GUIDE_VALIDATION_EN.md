# Validation Guide

This guide lists remaining steps to make the bundle 100% functional.

1. **Filter Implementation**  
   - Complete `GeoFilterListener`:  
     - VPN detection (`data['proxy']`)  
     - Crawler/User-Agent (`blocked_crawlers`)  
     - Flood/ping (RateLimiter)

2. **Real Configuration**  
   - Check `config/packages/geolocator.yaml` (alias geolConfig.yaml)  
   - Enable `redis_enabled` or `rabbit_enabled` as needed  
   - Set required environment variables

3. **Composer & Autoload**  
   - Run `composer dump-autoload`  
   - Verify `extra.symfony.bundle` and `bundles.php`

4. **Basic Tests**  
   - Run `vendor/bin/pest`  
   - WebTestCase tests for:  
     - Blocked IP → 403  
     - Whitelist → access granted  
     - Redis cache  
     - RabbitMQ mode

5. **Manual Execution**  
   - `bin/console server:run`  
   - Routes: `/__geo/debug`, `/`, `/admin/geolocator`  
   - Test `X-Forwarded-For`

