# GeolocatorBundle

Ce repository contient deux versions du guide d'utilisation du bundle :

* 📘 [README français](README.fr.md)
* 📗 [README English](README.en.md)

Le fichier ci-dessus est un index léger pour naviguer vers le guide complet dans la langue de votre choix.


## Démarrage rapide / Getting Started

Pour aider le développeur à installer et configurer le bundle :

1. **Installation**

    * Composer : `composer require geolocator-bundle`
    * Flex : vérifiez que `App\Bundle\GeolocatorBundle\GeolocatorBundle` apparaît bien dans `config/bundles.php`

2. **Paramétrage**

    * Copiez `config/packages/geolocator.yaml` et ajustez les options :

        * `enabled` : activer/désactiver
        * `redis_enabled` / `rabbit_enabled`
        * DSN providers : `GEOLOCATOR_PROVIDERS_DSN`
        * `TRUSTED_PROXIES`, `MESSENGER_TRANSPORT_DSN`, etc.
    * Définissez les variables d'environnement dans `.env.local` ou serveur.

3. **Validation**

    * Exécutez `composer dump-autoload`
    * Lancez `vendor/bin/pest` et vos WebTestCase
    * Démarrez le serveur : `bin/console server:run`
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

Le squelette du bundle est bien en place, mais pour qu'il soit « fonctionnel » à 100 % il reste quelques étapes, détaillées dans [docs/GUIDE\_VALIDATION.md](docs/GUIDE_VALIDATION.md) :

1. **Implémentation des filtres**
# GeolocatorBundle

Un bundle Symfony pour la géolocalisation des utilisateurs, la détection de VPN et le filtrage par pays.

## Fonctionnalités

- **Géolocalisation IP** avec plusieurs fournisseurs (ipapi, ipwhois, ipqualityscore)
- **Système de bannissement** avec tentatives et durées configurables
- **Filtrage par pays** (listes d'autorisation/blocage)
- **Détection de VPN/proxy/Tor**
- **Filtrage des robots d'indexation**
- **Stockage flexible** (mémoire, JSON, Redis)
- **Intégration avec le Profiler Symfony**

## Installation

1. Installer le bundle via Composer (ou l'intégrer directement dans votre projet)

```bash
composer require app/geolocator-bundle
```

2. Configurer le bundle dans `config/packages/geolocator.yaml`

```yaml
geolocator:
  enabled: true
  # Autres configurations...
```

3. Ajouter les routes dans `config/routes/geolocator.yaml`

```yaml
geolocator:
    resource: '@App\Bundle\GeolocatorBundle/Controller/'
    type: annotation
```

4. Configurer la clé API dans `.env` si vous utilisez IPQualityScore

```
IPQUALITYSCORE_APIKEY=your_api_key_here
```

## Configuration

Le bundle est hautement configurable. Voici les options principales :

```yaml
geolocator:
  enabled: true                # Activer/désactiver tout le bundle
  event_bridge_service: null   # Service externe pour déléguer les événements

  ip_filter_flags:             # En-têtes HTTP à vérifier pour l'IP client
      - 'X-Forwarded-For'
      - 'Client-Ip'

  providers:                   # Configuration des fournisseurs de géolocalisation
    default: 'ipapi'           # Fournisseur principal
    list:                      # Liste des fournisseurs disponibles
      ipapi:
        dsn: 'https://ipapi.co/{ip}/json/'
      ipwhois:
        dsn: 'https://ipwhois.app/json/{ip}'
      ipqualityscore:
        dsn: 'https://ipqualityscore.com/api/json/ip/{apikey}/{ip}'
        apikey: '%env(IPQUALITYSCORE_APIKEY)%'
    fallback: [ 'ipwhois', 'ipapi' ]  # Fournisseurs de secours

  storage:                     # Configuration du stockage
    type: 'json'               # 'memory', 'json', 'redis'
    file: '%kernel.project_dir%/var/bans.json'
    redis_dsn: 'redis://localhost'

  bans:                        # Configuration des bannissements
    max_attempts: 10           # Nombre de tentatives avant bannissement
    ttl: 3600                  # Durée en secondes
    ban_duration: '1 hour'     # Format: '1 hour', '30 minutes', '1 day'
    permanent_countries: [ 'RU', 'CN' ]  # Pays à bannir définitivement

  country_filters:             # Filtrage par pays
    allow: [ 'FR', 'BE' ]      # Autoriser uniquement ces pays
    block: [ 'RU', 'CN' ]      # Bloquer ces pays

  ip_filter_flags:             # En-têtes HTTP pour la détection d'IP
    - 'X-Forwarded-For'
    - 'X-Forwarded-Host'
    - 'X-Forwarded-Proto'

  trusted_proxies:             # IPs des proxies de confiance
    - '127.0.0.1'
    - '10.0.0.0/8'              # Notation CIDR supportée

  vpn_detection:               # Détection de VPN
    enabled: true
    provider: 'ipqualityscore'  # Fournisseur à utiliser
    allowed_ips: []            # IPs autorisées même si VPN

  crawler_filter:              # Filtrage des robots
    enabled: true
    allow_known: false         # Autoriser les robots connus (Google, Bing...)

  redirect_on_ban: '/banned'   # URL de redirection si banni

  log_channel: 'geolocator'    # Canal de log
  log_level: 'warning'         # Niveau minimal de log

  profiler:                    # Configuration du profiler
    enabled: true

  simulate: false              # Mode simulation (ne bloque pas)
```

## Utilisation

### Injection du service

```php
use App\Bundle\GeolocatorBundle\Service\GeolocatorService;

class MyController
{
    public function someAction(GeolocatorService $geolocator)
    {
        // Utiliser le service
    }
}
```

### Filtrage avec l'attribut GeoFilter

Vous pouvez utiliser l'attribut `GeoFilter` pour filtrer automatiquement les requêtes en fonction de critères géographiques :

```php
use GeolocatorBundle\Attribute\GeoFilter;

// Filtrage au niveau de la classe (s'applique à toutes les méthodes)
#[GeoFilter(allowedCountries: ['FR', 'BE'], blockVpn: true)]
class SecuredController
{
    // Cette méthode hérite des filtres de la classe
    public function index()
    {
        // ...    
    }

    // Cette méthode a ses propres règles qui remplacent celles de la classe
    #[GeoFilter(allowedCountries: ['FR'], allowedRanges: ['127.0.0.1/32'])]
    public function admin()
    {
        // ...    
    }
}
```

L'attribut `GeoFilter` accepte les paramètres suivants :

- `allowedCountries` : Liste des codes pays autorisés (ex: `['FR', 'BE']`)
- `blockedCountries` : Liste des codes pays bloqués (ex: `['RU', 'CN']`)
- `allowedRanges` : Liste des plages d'IP autorisées (ex: `['192.168.1.0/24']`)
- `blockedRanges` : Liste des plages d'IP bloquées (ex: `['10.0.0.0/8']`)
- `blockVpn` : Bloquer les VPN/proxy/Tor (`true`/`false`)
- `blockCrawlers` : Bloquer les robots d'indexation (`true`/`false`)
- `redirectUrl` : URL de redirection en cas de blocage

### Vérification manuelle d'une requête

```php
$result = $geolocator->processRequest($request);

if ($result->isBanned()) {
    // L'utilisateur est banni
    $reason = $result->getReason();
    // ...
} else {
    // L'utilisateur est autorisé
    $geoLocation = $result->getGeoLocation();
    $country = $geoLocation->getCountryCode();
    // ...
}
```

### Obtenir la géolocalisation d'une IP

```php
$geoLocation = $geolocator->getGeoLocation('8.8.8.8');

$country = $geoLocation->getCountryCode();     // 'US'
$city = $geoLocation->getCity();               // 'Mountain View'
$isVpn = $geoLocation->isVpn();                // false
```

## Commandes console

Le bundle fournit plusieurs commandes utiles :

- `geolocator:test [ip]` - Teste la géolocalisation d'une IP
- `geolocator:list-bans` - Liste toutes les IPs bannies
- `geolocator:clean-bans` - Nettoie les bannissements expirés
- `geolocator:unban <ip>` - Débannit une IP spécifique

## Événements

Le bundle déclenche les événements suivants :

- `geolocator.ban.detected` - Quand une IP déjà bannie est détectée
- `geolocator.ban.added` - Quand une IP est bannie
- `geolocator.ban.simulated` - En mode simulation quand une IP aurait été bannie
- `geolocator.request.allowed` - Quand une requête est autorisée

## Personnalisation

### Page de bannissement

La page de bannissement par défaut peut être remplacée en surchargeant le template :

```twig
{# templates/bundles/GeolocatorBundle/banned.html.twig #}
{% extends '@Geolocator/banned.html.twig' %}

{% block content %}
    {# Votre contenu personnalisé #}
{% endblock %}
```

### Providers personnalisés

Vous pouvez créer vos propres providers en implémentant l'interface `ProviderInterface` et en les taguant comme service :

```yaml
app.geolocator.provider.custom:
    class: App\GeolocatorProvider\CustomProvider
    arguments:
        $httpClient: '@http_client'
        $config: { dsn: 'https://custom-api.com/{ip}' }
    tags:
        - { name: 'geolocator.provider', alias: 'custom' }
```

## Licence

Ce bundle est sous licence MIT.
    * `GeoFilterSubscriber` contient la logique de filtrage géographique et peut être étendu pour la détection VPN, User-Agent, flood, etc.
    * Traduction des règles métier en appels `IpUtils::checkIp`, comparaisons de pays/ASN/ISP et déclenchement du ban ou bypass.

2. **Configuration réelle**

    * Vérifiez que `config/packages/geolocator.yaml` est bien chargé (alias `geolConfig.yaml`) : activez `rabbit_enabled` ou `redis_enabled` selon votre usage ; en environnement de test, désactivez-les pour rester en mode synchrone/filesystem.
    * Assurez-vous que les variables d'environnement (`GEOLOCATOR_PROVIDERS_DSN`, `TRUSTED_PROXIES`, `MESSENGER_TRANSPORT_DSN`, etc.) sont définies.

3. **Composer & autoload**

    * Exécutez `composer dump-autoload` pour prendre en compte les namespaces.
    * Vérifiez que `extra.symfony.bundle` de `composer.json` pointe vers `App\Bundle\GeolocatorBundle\GeolocatorBundle` et qu'il apparaît dans `bundles.php`.

4. **Tests de base**
   Lancez :

    * `vendor/bin/pest` pour les tests unitaires.
    * Vos WebTestCase pour valider :

        * IP dans `blockedCountries` → 403.
        * Whitelist via `allowedRanges`, `allowedCountries` → accès autorisé.
        * Cache Redis évite un second appel géoloc.
        * Mode RabbitMQ délègue la géoloc via Messenger.

5. **Exécution manuelle**

    * `bin/console server:run` et test des routes (`/__geo/debug`, `/`, `/admin/geolocator`).
    * Testez avec l'en-tête `X-Forwarded-For` pour valider `IpResolver`.