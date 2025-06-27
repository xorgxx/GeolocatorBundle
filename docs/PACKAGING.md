# Packaging

- **Packagist** : publiez `xorg/geolocator-bundle` pour composer require.
- **Symfony Flex** : ajoutez dans `composer.json` :

```json
"extra": {
  "symfony": {
    "bundle": {
      "Xorg\\GeolocatorBundle\\XorgGeolocatorBundle": "all"
    }
  }
}
```
