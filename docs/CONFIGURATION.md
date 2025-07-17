# Configuration du GeolocatorBundle

## Configuration de base

La configuration du bundle se fait dans le fichier `config/packages/geolocator.yaml`. Voici un exemple complet de configuration :

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

## Configuration des fournisseurs

### Fournisseurs disponibles

Le bundle prend en charge plusieurs fournisseurs de géolocalisation :

- **ipapi** : Fournisseur gratuit avec limitations (1000 requêtes/jour)
- **ipwhois** : Fournisseur gratuit avec limitations (10000 requêtes/jour)
- **ipqualityscore** : Fournisseur payant avec détection avancée de VPN/Proxy (nécessite une clé API)

### Ajouter un fournisseur personnalisé

Vous pouvez ajouter votre propre fournisseur en implémentant l'interface `App\Bundle\GeolocatorBundle\Provider\ProviderInterface` et en l'enregistrant comme service avec le tag `geolocator.provider`.

```yaml
services:
    app.geolocator.provider.custom:
        class: App\Provider\CustomProvider
        arguments:
            $httpClient: '@http_client'
            $config: { dsn: 'https://custom-api.com/{ip}' }
        tags:
            - { name: 'geolocator.provider', alias: 'custom' }
```

## Configuration du stockage

### Types de stockage disponibles

- **memory** : Stockage en mémoire (non persistant, uniquement pour les tests)
- **json** : Stockage dans un fichier JSON
- **redis** : Stockage dans Redis (recommandé pour la production)

### Configuration Redis

Pour utiliser Redis, vous devez configurer le DSN Redis :

```yaml
geolocator:
  storage:
    type: 'redis'
    redis_dsn: '%env(REDIS_DSN)%'
```

Et définir la variable d'environnement :

```
REDIS_DSN=redis://localhost:6379
```

## Configuration des bannissements

### Durée des bannissements

Vous pouvez configurer la durée des bannissements temporaires :

```yaml
geolocator:
  bans:
    ban_duration: '1 day'
```

Formats acceptés : '30 seconds', '5 minutes', '2 hours', '1 day', '1 week', '1 month'

### Bannissements permanents

Vous pouvez configurer certains pays pour être bannis de façon permanente :

```yaml
geolocator:
  bans:
    permanent_countries: ['RU', 'CN', 'KP']
```

## Configuration des filtres

### Filtrage par pays

Vous pouvez filtrer les requêtes par pays en utilisant les listes d'autorisation ou de blocage :

```yaml
geolocator:
  country_filters:
    allow: ['FR', 'BE', 'CH', 'LU']  # Seuls ces pays sont autorisés
    block: ['RU', 'CN', 'KP']        # Ces pays sont bloqués
```

Si les deux listes sont définies, la liste d'autorisation (`allow`) est prioritaire.

### Détection de VPN

Vous pouvez activer la détection de VPN/Proxy :

```yaml
geolocator:
  vpn_detection:
    enabled: true
    provider: 'ipqualityscore'  # Fournisseur qui supporte la détection de VPN
    allowed_ips: ['123.45.67.89']  # IPs exemptées de la détection de VPN
```

### Filtrage des robots

Vous pouvez configurer le filtrage des robots d'indexation :

```yaml
geolocator:
  crawler_filter:
    enabled: true
    allow_known: true  # Autoriser les robots connus (Google, Bing...)
```

## Configuration du mode simulation

Vous pouvez activer le mode simulation pour tester vos règles sans bloquer réellement les utilisateurs :

```yaml
geolocator:
  simulate: true
```

En mode simulation, les actions de bannissement sont enregistrées mais non appliquées. Cela permet de vérifier vos règles en production sans risque.

## Configuration des logs

```yaml
geolocator:
  log_channel: 'geolocator'
  log_level: 'warning'  # Options: debug, info, warning, error, critical
```

## Configuration du profiler Symfony

Le bundle s'intègre au Profiler Symfony pour faciliter le débogage :

```yaml
geolocator:
  profiler:
    enabled: true  # Activé en développement, désactivé en production
```

## Configuration avancée

### Event Bridge Service

Vous pouvez configurer un service externe pour déléguer les événements :

```yaml
geolocator:
  event_bridge_service: 'app.event_bridge'
```

Cela permet d'intégrer le bundle avec des systèmes externes comme AWS EventBridge, RabbitMQ, ou d'autres systèmes de messagerie.

### En-têtes HTTP pour la résolution d'IP

Vous pouvez configurer les en-têtes HTTP à vérifier pour déterminer l'IP client :

```yaml
geolocator:
  ip_filter_flags:
    - 'X-Forwarded-For'
    - 'X-Real-IP'
    - 'Client-Ip'
```

Les en-têtes sont vérifiés dans l'ordre spécifié. Si aucun en-tête ne contient une IP valide, l'IP de la requête Symfony est utilisée.
