# Attribut GeoFilter

L'attribut `GeoFilter` vous permet de configurer des règles de filtrage géographique et IP directement sur vos contrôleurs ou méthodes de contrôleur Symfony.

## Utilisation de base

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
        // Cette méthode n'est accessible que depuis FR, BE ou CH,
        // mais pas depuis RU ou CN
        // De plus, les IPs de la plage 192.168.1.0/24 sont toujours autorisées,
        // mais l'IP 1.2.3.4 est toujours bloquée

        return new Response('Contenu sécurisé');
    }
}
```

## Options disponibles

### Filtrage par pays

```php
#[GeoFilter(
    allowedCountries: ['FR', 'BE'], // Seuls ces pays sont autorisés
    blockedCountries: ['RU', 'CN']   // Ces pays sont toujours bloqués
)]
```

Si `allowedCountries` est défini, seuls les pays listés sont autorisés.
Si `blockedCountries` est défini, ces pays sont toujours bloqués, même s'ils sont dans `allowedCountries`.

### Filtrage par IP

```php
#[GeoFilter(
    allowedIps: ['127.0.0.1', '192.168.1.0/24', '10.0.0.*'], // IPs/plages autorisées
    blockedIps: ['1.2.3.4', '5.6.7.0/24'],                     // IPs/plages bloquées
    allowIpsExclusive: true                                      // Mode liste blanche exclusive
)]
```

Le filtrage IP supporte plusieurs formats :
- IP exacte : `127.0.0.1`
- Plage CIDR : `192.168.1.0/24`
- Wildcard : `10.0.0.*`

Si `allowIpsExclusive` est `true`, seules les IPs dans `allowedIps` sont autorisées, toutes les autres sont bloquées.

### Autres options

```php
#[GeoFilter(
    blockVpn: true,            // Bloquer les VPNs, proxies et Tor (par défaut)
    blockCrawlers: true,        // Bloquer les robots d'indexation (désactivé par défaut)
    redirectUrl: '/blocked'     // URL de redirection personnalisée en cas de blocage
)]
```

## Application au niveau de la classe

Vous pouvez appliquer l'attribut à une classe entière pour protéger toutes ses méthodes :

```php
#[GeoFilter(allowedCountries: ['FR', 'BE'])]
class SecureController
{
    // Toutes les méthodes de ce contrôleur ne sont accessibles que depuis la France et la Belgique
}
```

## Surcharge au niveau des méthodes

Si un attribut est défini à la fois sur la classe et sur une méthode, c'est l'attribut de la méthode qui prévaut :

```php
#[GeoFilter(allowedCountries: ['FR', 'BE'])]
class SecureController
{
    #[GeoFilter(allowedCountries: ['FR', 'BE', 'CH', 'LU'])]
    public function lessRestrictedAction()
    {
        // Accessible depuis FR, BE, CH et LU
    }

    public function defaultAction()
    {
        // Accessible seulement depuis FR et BE (règle de la classe)
    }
}
```

## Ordre de priorité des règles

Les règles sont évaluées dans cet ordre :

1. Filtrage IP (liste blanche prioritaire)
2. Filtrage par pays
3. Détection de VPN
4. Filtrage des crawlers

Cela signifie qu'une IP dans la liste d'autorisation pourra accéder même si le pays est bloqué.

## Redirection

En cas de blocage, l'utilisateur est redirigé vers :

1. L'URL spécifiée dans `redirectUrl` de l'attribut
2. Si non définie, l'URL globale configurée dans `geolocator.redirect_on_ban`
3. Si non définie, `/blocked` par défaut

## Exemples d'utilisation

### API sécurisée pour l'Europe uniquement

```php
#[GeoFilter(allowedCountries: ['FR', 'DE', 'IT', 'ES', 'BE', 'NL', 'LU', 'CH', 'AT'])]
class ApiController
```

### Contenu réservé à certaines IPs

```php
#[GeoFilter(allowedIps: ['10.0.0.0/8', '192.168.0.0/16'], allowIpsExclusive: true)]
public function intranetAction()
```

### Bloquer les VPNs mais autoriser certaines IPs

```php
#[GeoFilter(blockVpn: true, allowedIps: ['203.0.113.0/24'])]
public function secureButAllowCertainProxies()
```

### Zone d'administration hautement sécurisée

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
