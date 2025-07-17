# Stratégie de Fallback du GeolocatorBundle

## Introduction

Le GeolocatorBundle implémente une stratégie de fallback robuste pour gérer les cas d'erreurs ou d'indisponibilité des fournisseurs de géolocalisation. Cette documentation détaille le fonctionnement de cette stratégie et comment la configurer.

## Principe de fonctionnement

La stratégie de fallback fonctionne sur plusieurs niveaux :

1. **Fournisseur principal** : Le bundle tente d'abord d'utiliser le fournisseur défini comme `default` dans la configuration.
2. **Fournisseurs de secours** : En cas d'échec du fournisseur principal, le bundle utilise les fournisseurs de secours définis dans la liste `fallback`.
3. **Exclusion temporaire** : Les fournisseurs qui échouent sont temporairement exclus pour éviter de continuer à faire des requêtes qui échoueront.
4. **Réintégration automatique** : Après un délai configurable, les fournisseurs exclus sont réintégrés pour tester à nouveau leur disponibilité.

## Configuration

### Configuration de base

```yaml
geolocator:
  providers:
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
```

### Configuration avancée

```yaml
geolocator:
  providers:
    # Configuration de base...
    timeout: 3                 # Timeout en secondes pour les requêtes HTTP
    retry_count: 2             # Nombre de tentatives avant de passer au fournisseur suivant
    exclusion_time: 300        # Temps d'exclusion en secondes pour un fournisseur défaillant
    check_interval: 60         # Intervalle de vérification en secondes pour les fournisseurs exclus
```

## Comportement en cas d'erreur

### Erreurs gérées

Le bundle gère les types d'erreurs suivants :

- **Timeout** : Le fournisseur ne répond pas dans le délai imparti
- **Erreur HTTP** : Le fournisseur renvoie un code d'erreur HTTP (4xx, 5xx)
- **Réponse invalide** : Le fournisseur renvoie une réponse qui ne peut pas être interprétée
- **Limite atteinte** : Le fournisseur indique que la limite de requêtes est atteinte

### Processus de fallback

1. Le bundle tente d'utiliser le fournisseur principal avec jusqu'à `retry_count` tentatives
2. Si toutes les tentatives échouent, le fournisseur est marqué comme indisponible pendant `exclusion_time` secondes
3. Le bundle passe au premier fournisseur de secours et répète le processus
4. Si tous les fournisseurs échouent, une exception est levée

## Cache et optimisation

Pour optimiser les performances et réduire le nombre de requêtes vers les fournisseurs :

- Les résultats de géolocalisation sont mis en cache (PSR-6)
- Les fournisseurs défaillants sont exclus temporairement
- Le fallback n'est utilisé que si nécessaire

## Exemples pratiques

### Scénario 1 : Fournisseur principal temporairement indisponible

1. Le fournisseur principal (ipapi) est indisponible (timeout)
2. Après `retry_count` tentatives, le bundle passe au premier fournisseur de secours (ipwhois)
3. La géolocalisation est obtenue via ipwhois
4. Le fournisseur ipapi est marqué comme indisponible pendant `exclusion_time` secondes
5. Pour les requêtes suivantes pendant cette période, le bundle utilise directement ipwhois

### Scénario 2 : Tous les fournisseurs sont indisponibles

1. Tous les fournisseurs sont indisponibles (timeout ou erreur)
2. Le bundle tente tous les fournisseurs configurés
3. Si tous échouent, une exception est levée
4. L'exception est gérée selon la configuration (mode simulate, comportement par défaut)

## Surveillance et journalisation

Le bundle journalise les événements suivants pour faciliter la surveillance :

- Changement de fournisseur (fallback activé)
- Exclusion d'un fournisseur
- Réintégration d'un fournisseur
- Échec de tous les fournisseurs

## Configuration recommandée pour la production

```yaml
geolocator:
  providers:
    default: 'ipapi'           # Gratuit mais avec limite
    list:
      ipapi:
        dsn: 'https://ipapi.co/{ip}/json/'
      ipwhois:
        dsn: 'https://ipwhois.app/json/{ip}'
      ipqualityscore:         # Payant mais fiable
        dsn: 'https://ipqualityscore.com/api/json/ip/{apikey}/{ip}'
        apikey: '%env(IPQUALITYSCORE_APIKEY)%'
    fallback: [ 'ipqualityscore', 'ipwhois' ]
    timeout: 2
    retry_count: 1
    exclusion_time: 600
    check_interval: 120
```

Cette configuration utilise :
- Un fournisseur gratuit comme principal (ipapi)
- Un fournisseur payant mais fiable comme premier fallback (ipqualityscore)
- Un autre fournisseur gratuit comme deuxième fallback (ipwhois)
- Des timeouts courts et peu de tentatives pour éviter les latences
- Une exclusion longue (10 minutes) pour éviter de solliciter les fournisseurs défaillants
