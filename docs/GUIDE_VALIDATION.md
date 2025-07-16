# Guide de Validation

Ce guide liste les étapes restantes pour rendre le bundle 100% fonctionnel.

1. **Implémentation des filtres**  
   - Complétez `GeoFilterSubscriber` :  
     - VPN (data['proxy'])  
     - Crawler/User-Agent (`blocked_crawlers`)  
     - Flood/ping (RateLimiter)

2. **Configuration réelle**  
   - Vérifiez `config/packages/geolocator.yaml` (alias geolConfig.yaml)  
   - Activez `redis_enabled` ou `rabbit_enabled` selon votre usage  
   - Définissez les variables d’environnement nécessaires

3. **Composer & autoload**  
   - `composer dump-autoload`  
   - Vérifiez `extra.symfony.bundle` et `bundles.php`

4. **Tests de base**  
   - Lancer `vendor/bin/pest`  
   - Tests WebTestCase pour :  
     - IP bloquée → 403  
     - Whitelist → accès  
     - Cache Redis  
     - Mode RabbitMQ

5. **Exécution manuelle**  
   - `bin/console server:run`  
   - Routes : `/__geo/debug`, `/`, `/admin/geolocator`  
   - Tester `X-Forwarded-For`

