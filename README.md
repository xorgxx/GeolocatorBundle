# GeolocatorBundle

Ce repository contient deux versions du guide d’utilisation du bundle :

* 📘 [README français](README.fr.md)
* 📗 [README English](README.en.md)

Le fichier ci-dessus est un index léger pour naviguer vers le guide complet dans la langue de votre choix.


## Démarrage rapide / Getting Started

Pour aider le développeur à installer et configurer le bundle :

1. **Installation**

    * Composer : `composer require geolocator-bundle`
    * Flex : vérifiez que `GeolocatorBundle\GeolocatorBundle` apparaît bien dans `config/bundles.php`

2. **Paramétrage**

    * Copiez `config/packages/geolocator.yaml` et ajustez les options :

        * `enabled` : activer/désactiver
        * `redis_enabled` / `rabbit_enabled`
        * DSN providers : `GEOLOCATOR_PROVIDERS_DSN`
        * `TRUSTED_PROXIES`, `MESSENGER_TRANSPORT_DSN`, etc.
    * Définissez les variables d’environnement dans `.env.local` ou serveur.

3. **Validation**

    * Exécutez `composer dump-autoload`
    * Lancez `vendor/bin/pest` et vos WebTestCase
    * Démarrez le serveur : `bin/console server:run`
    * Testez la route `/__geo/debug` et le dashboard admin

---

## Complete Guide

* If you develop in French, open [README.fr.md](README.fr.md)
* If you prefer English, open [README.en.md](README.en.md)

## Advanced Configuration

The main configuration file **`geolConfig.yaml`** (alias `config/packages/geolocator.yaml`) allows you to customize:

* Bundle activation (`enabled`)
* Cache modes (`redis_enabled`, `cache_pool`)
* Synchronous or asynchronous processing (`rabbit_enabled`, `messenger_transport`)
* IP filtering parameters, countries, ASN, ISP, VPN, User-Agent, flood...

For more details, consult the documentation:

* French: [docs/CONFIGURATION.md](docs/CONFIGURATION.md)
* English: [docs/CONFIGURATION\_EN.md](docs/CONFIGURATION_EN.md)

---

## Checklist de validation

Le squelette du bundle est bien en place, mais pour qu’il soit « fonctionnel » à 100 % il reste quelques étapes, détaillées dans [docs/GUIDE\_VALIDATION.md](docs/GUIDE_VALIDATION.md) :

1. **Implémentation des filtres**

    * `GeoFilterListener` contient aujourd’hui des `// TODO` pour la logique VPN, User-Agent, flood, etc.
    * Traduction des règles métier en appels `IpUtils::checkIp`, comparaisons de pays/ASN/ISP et déclenchement du ban ou bypass.

2. **Configuration réelle**

    * Vérifiez que `config/packages/geolocator.yaml` est bien chargé (alias `geolConfig.yaml`) : activez `rabbit_enabled` ou `redis_enabled` selon votre usage ; en environnement de test, désactivez-les pour rester en mode synchrone/filesystem.
    * Assurez-vous que les variables d’environnement (`GEOLOCATOR_PROVIDERS_DSN`, `TRUSTED_PROXIES`, `MESSENGER_TRANSPORT_DSN`, etc.) sont définies.

3. **Composer & autoload**

    * Exécutez `composer dump-autoload` pour prendre en compte les namespaces.
    * Vérifiez que `extra.symfony.bundle` de `composer.json` pointe vers `GeolocatorBundle\GeolocatorBundle` et qu’il apparaît dans `bundles.php`.

4. **Tests de base**
   Lancez :

    * `vendor/bin/pest` pour les tests unitaires.
    * Vos WebTestCase pour valider :

        * IP dans `blockedCountries` → 403.
        * Whitelist via `allowedRanges`, `allowedCountries` → accès autorisé.
        * Cache Redis évite un second appel géoloc.
        * Mode RabbitMQ délègue la géoloc via Messenger.

5. **Exécution manuelle**

    * `bin/console server:run` et test des routes (`/__geo/debug`, `/`, `/admin/geolocator`).
    * Testez avec l’en-tête `X-Forwarded-For` pour valider `IpResolver`.

