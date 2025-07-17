# Filtre IP

Le GeolocatorBundle permet de configurer des listes d'adresses IP autorisées (whitelist) et bloquées (blacklist) pour contrôler l'accès à votre application de manière précise.

## Configuration

Le filtre IP se configure dans votre fichier de configuration Symfony :

```yaml
# config/packages/geolocator.yaml
geolocator:
  ip_filters:
    enabled: true                   # Active ou désactive le filtre IP
    allow_list_exclusive: false     # Si true, seules les IPs dans allow_list sont autorisées
    allow_list:                     # Liste des IPs autorisées
      - '127.0.0.1'                 # IP exacte
      - '192.168.1.0/24'            # Plage CIDR
      - '10.0.0.*'                  # Wildcard
    block_list:                     # Liste des IPs bloquées
      - '1.2.3.4'
      - '5.6.7.0/24'
```

## Formats supportés

Le filtre IP supporte différents formats :

1. **IP exacte** : `127.0.0.1`
2. **Plage CIDR** : `192.168.1.0/24` (tous les IPs du réseau 192.168.1.0 avec un masque de 24 bits)
3. **Wildcard** : `10.0.0.*` (toutes les IPs commençant par 10.0.0)

## Modes de fonctionnement

### Mode standard

Par défaut (`allow_list_exclusive: false`), le filtre fonctionne ainsi :

1. Si l'IP est dans la `allow_list`, elle est **toujours autorisée**
2. Si l'IP est dans la `block_list`, elle est **toujours bloquée**
3. Si l'IP n'est dans aucune liste, elle est **autorisée par défaut**

### Mode liste blanche exclusive

Lorsque `allow_list_exclusive: true`, le fonctionnement devient :

1. Si l'IP est dans la `allow_list`, elle est **autorisée**
2. Si l'IP n'est pas dans la `allow_list`, elle est **bloquée**
3. La `block_list` est ignorée dans ce mode

Ce mode est utile pour restreindre l'accès à un ensemble défini d'adresses IP uniquement.

## Utilisation programmatique

Vous pouvez également utiliser le filtre IP dans votre code :

```php
// Vérifier si une IP est autorisée
$isAllowed = $geolocatorService->isIpAllowed('192.168.1.10');

// Obtenir le service directement
$ipFilter = $container->get('geolocator.ip_filter');

// Vérifier si une IP est dans la liste d'autorisation
$isInAllowList = $ipFilter->isInAllowList('192.168.1.10');

// Vérifier si une IP est dans la liste de blocage
$isInBlockList = $ipFilter->isInBlockList('1.2.3.4');
```

## Ordre de traitement

Dans le cycle de traitement d'une requête, le filtre IP est vérifié **avant** les autres filtres (pays, VPN, etc.), ce qui vous permet de créer des exceptions pour certaines adresses IP même si elles proviennent de pays normalement bloqués.

## Cas d'utilisation

1. **Environnement de test** : Autoriser uniquement les IPs de votre entreprise
2. **API privée** : Restreindre l'accès à des IPs de partenaires connus
3. **Prévention d'abus** : Bloquer des IPs spécifiques ayant montré un comportement suspect
4. **Exceptions** : Autoriser certaines IPs même si elles proviennent de pays ou de VPNs normalement bloqués

## Journalisation

Toutes les actions du filtre IP sont enregistrées dans le journal `geolocator.log` avec les informations suivantes :
- IP vérifiée
- Résultat (autorisée/bloquée)
- Règle de correspondance

Ceci vous permet de déboguer facilement la configuration du filtre IP.
