# Sécurité de GeolocatorBundle

## Configuration des proxies de confiance

> **Important** : La configuration correcte des proxies de confiance est essentielle pour la sécurité de votre application. Une mauvaise configuration peut permettre aux attaquants de falsifier leur adresse IP.

Lorsque votre application est derrière un proxy inverse, un équilibreur de charge ou un CDN, la détection de l'adresse IP du client peut être compromise si elle n'est pas correctement configurée. Symfony utilise le mécanisme de "trusted proxies" pour sécuriser cette détection.

### Pourquoi est-ce important ?

Sans configuration appropriée, un attaquant pourrait falsifier l'en-tête `X-Forwarded-For` pour contourner les restrictions géographiques ou les bannissements IP.

### Configuration recommandée

Dans votre fichier `config/packages/geolocator.yaml` :

```yaml
geolocator:
  # Configuration existante...

  # En-têtes à prendre en compte pour la détection d'IP
  ip_filter_flags:
    - 'X-Forwarded-For'
    - 'X-Forwarded-Host'
    - 'X-Forwarded-Proto'

  # Liste des proxies de confiance
  trusted_proxies:
    - '127.0.0.1'          # Localhost
    - '10.0.0.0/8'          # Réseau privé
    - '172.16.0.0/12'       # Réseau privé
    - '192.168.0.0/16'      # Réseau privé
    - 'IP_DE_VOTRE_PROXY'   # Adresse IP de votre proxy/load balancer
```

### Utilisation en production

En production, il est recommandé d'utiliser des variables d'environnement :

```env
# .env.local ou configuration du serveur
GEOLOCATOR_TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8,IP_DE_VOTRE_PROXY
```

Et dans votre fichier de configuration :

```yaml
geolocator:
  trusted_proxies: '%env(csv:GEOLOCATOR_TRUSTED_PROXIES)%'
```

### Avec un CDN ou un service cloud

Si vous utilisez un CDN comme Cloudflare ou un service cloud comme AWS, vous devrez ajouter leurs plages d'adresses IP à votre liste de proxies de confiance. Consultez la documentation de votre fournisseur pour obtenir ces plages.

## Autres bonnes pratiques

1. **Limitez les en-têtes de confiance** - N'activez que les en-têtes dont vous avez besoin
2. **Validez les IP** - Utilisez toujours les fonctions de validation d'IP appropriées
3. **Journalisation** - Activez la journalisation des accès pour surveiller les tentatives suspectes
4. **Tests** - Testez régulièrement votre configuration avec différentes configurations de proxy
