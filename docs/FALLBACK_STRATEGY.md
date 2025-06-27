# Stratégie de Fallback du GeolocatorBundle

## Introduction

Le GeolocatorBundle implémente une stratégie robuste de fallback pour gérer les cas où les fournisseurs de géolocalisation rencontrent des problèmes. Cette documentation explique comment le système gère les différents types d'erreurs et assure la continuité du service.

## Types d'erreurs gérées

1. **Timeout** : Le fournisseur ne répond pas dans le délai imparti (par défaut 3 secondes)
2. **Erreur HTTP** : Le fournisseur renvoie un code d'erreur (4xx, 5xx)
3. **Refus d'accès** : Le fournisseur refuse l'accès (403, 429 rate limit)
4. **Erreur de parsing** : Les données reçues ne sont pas dans le format attendu

## Fonctionnement du fallback

### 1. Principe de base

Le `GeolocatorService` essaie d'obtenir des données de géolocalisation selon la séquence suivante :

```
Cache → Fournisseur 1 → Fournisseur 2 → ... → Fournisseur N → Réponse par défaut
```

### 2. Mécanisme d'exclusion temporaire

- Après un certain nombre d'erreurs (par défaut 3), un fournisseur est marqué comme indisponible
- Les fournisseurs indisponibles sont automatiquement ignorés par le `ProviderManager`
- Le système teste périodiquement si les fournisseurs redeviennent disponibles

### 3. Configuration

```yaml
# config/packages/geolocator.yaml
geolocator:
  fallback_enabled: true     # Active/désactive la stratégie de fallback
  max_retries: 3             # Nombre maximum de tentatives avant de renvoyer une réponse par défaut
  provider_timeout: 3        # Délai d'attente en secondes pour chaque fournisseur
  provider_errors_threshold: 3  # Nombre d'erreurs avant de marquer un fournisseur comme indisponible
```

## Réponse par défaut

Si tous les fournisseurs échouent, le système renvoie une réponse par défaut avec :

```php
[
    'error' => true,
    'ip' => $ip,
    'message' => 'Échec de la géolocalisation après X tentatives',
    'last_error' => $lastError,  // Détails de la dernière erreur
    'fallback' => true,
    'country' => null,
    'continent' => null,
    'is_vpn' => false,  // Par défaut, on considère que ce n'est pas un VPN
    'asn' => null,
    'isp' => null
]
```

## Logging et monitoring

Le bundle enregistre des logs détaillés sur les échecs de fournisseurs :

- **info** : Tentatives normales et succès
- **warning** : Erreurs temporaires avec un fournisseur
- **error** : Problèmes graves avec un fournisseur spécifique
- **critical** : Tous les fournisseurs sont indisponibles

Vous pouvez suivre ces logs pour détecter des problèmes récurrents et ajuster votre configuration.

## Bonnes pratiques

1. **Configurer plusieurs fournisseurs** : Toujours avoir au moins 2-3 fournisseurs différents
2. **Vérifier les quotas** : S'assurer que vos quotas API sont suffisants
3. **Mettre en cache** : Activer la mise en cache pour réduire la dépendance aux fournisseurs
4. **Surveiller les logs** : Mettre en place des alertes sur les logs critiques

## Exemple d'utilisation dans le code

```php
// Dans un controller ou service
public function checkAccess(string $ip, GeolocatorService $geolocator)
{
    $geoData = $geolocator->locateIp($ip);

    // Vérifier si on a une erreur de géolocalisation
    if (isset($geoData['error']) && $geoData['error'] === true) {
        // Décider quoi faire en cas d'erreur :
        // - Autoriser l'accès par défaut ?
        // - Refuser l'accès par précaution ?
        // - Utiliser des données de secours ?

        // Exemple : autoriser par défaut si configuration en ce sens
        if ($this->parameterBag->get('geolocator.allow_on_error')) {
            return true;
        }

        return false;
    }

    // Utiliser les données normalement
    $country = $geoData['country'];
    // ...
}
```
