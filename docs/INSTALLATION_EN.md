# Installation

Pour installer le bundle dans votre projet Symfony 7.3 / PHP 8.3 :

```bash
composer require xorg/geolocator-bundle
```

Avec Symfony Flex, le bundle est automatiquement activé.  
Sinon, ajoutez manuellement dans `bundles.php` :

```php
return [
    // ...
    Xorg\GeolocatorBundle\XorgGeolocatorBundle::class => ['all' => true],
];
```
