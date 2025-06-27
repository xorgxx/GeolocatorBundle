# Security & Performance

- **Validation des DSN** : configurez un whitelist de domaines pour prévenir le SSRF.
- **HttpClient** : définissez `timeout` et `retry` dans `framework.yaml` :

```yaml
framework:
  http_client:
    default_options:
      timeout: 2
      max_retries: 1
```

- **Appels async** : activez `rabbit_enabled: true` pour déléguer la géoloc à Messenger.
- **Cache Redis** : `redis_enabled: true` réduit la latence et le nombre d’appels externes.
