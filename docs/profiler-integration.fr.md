# Intégration avec la barre de profil Symfony

Le GeolocatorBundle s'intègre parfaitement avec la barre de profil Symfony (Web Debug Toolbar) pour fournir des informations détaillées sur la géolocalisation des visiteurs pendant le développement.

## Fonctionnalités

L'intégration avec le profiler permet de :

1. **Visualiser rapidement** les informations de géolocalisation dans la barre d'outils
2. **Identifier les IPs bloquées** avec un indicateur visuel
3. **Détecter les VPNs et les crawlers** avec des alertes spécifiques
4. **Consulter les détails** complets dans le panel du profiler

## Activation

L'intégration avec le profiler est automatiquement activée en environnement de développement (`dev`) lorsque le composant `symfony/web-profiler-bundle` est installé :

```bash
composer require --dev symfony/web-profiler-bundle
```

Le service est déjà configuré dans `services.yaml` :

```yaml
# src/Resources/config/services.yaml
geolocator.data_collector:
    class: GeolocatorBundle\DataCollector\GeolocatorDataCollector
    arguments:
        - '@geolocator.service'
    tags:
        - { name: data_collector, template: '@Geolocator/data_collector/template.html.twig', id: geolocator, priority: 255 }
```

Aucune configuration supplémentaire n'est nécessaire.

## Utilisation

### Barre d'outils

La barre d'outils affiche :

- **Le code pays** de l'IP détectée
- **Un indicateur coloré** : 
  - Rouge : IP bloquée
  - Jaune : VPN/Proxy détecté
  - Normal : Aucun problème détecté

En passant la souris sur l'icône, un tooltip affiche des informations supplémentaires :

- L'adresse IP
- Le pays complet
- La ville
- Le statut (bloqué ou non)
- Si un VPN/Proxy est détecté
- Si un crawler est détecté
- Si le mode simulation est actif

### Panel détaillé

En cliquant sur l'icône dans la barre d'outils, vous accédez au panel détaillé qui affiche :

1. **Informations générales** :
   - IP, pays, ville, coordonnées
   - Provider utilisé pour la géolocalisation

2. **Statuts** :
   - Bloqué/autorisé
   - VPN/Proxy détecté
   - Crawler détecté
   - Mode simulation actif/inactif
   - Mode asynchrone disponible/indisponible

3. **Informations de bannissement** (si l'IP est bloquée) :
   - Raison du blocage
   - Date de début
   - Durée/expiration

## Avantages pour le développement

- **Débogage simplifié** : Visualisez rapidement les informations de géolocalisation sans avoir à ajouter de code de débogage
- **Test des règles** : Testez vos règles de filtrage par pays et détection de VPN en temps réel
- **Vérification du mode simulation** : Confirmez que le mode simulation fonctionne correctement
- **Surveillance des bannissements** : Suivez les IPs bloquées et les raisons associées

## Exemple d'utilisation

1. Accédez à votre application en environnement de développement
2. Observez l'icône de géolocalisation dans la barre d'outils
3. Passez la souris dessus pour voir les informations de base
4. Cliquez pour accéder au panel détaillé
5. Testez différentes règles et observez les changements dans le profiler

## Capture d'écran

![Profiler Geolocator](profiler-screenshot.png)

## Notes

- Cette fonctionnalité est automatiquement désactivée en environnement de production
- Pour simuler différents pays, vous pouvez utiliser des VPNs ou modifier manuellement les informations de géolocalisation en étendant les providers
