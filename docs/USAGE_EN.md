# Usage

Utilisez l’attribut `#[GeoFilter]` sur vos contrôleurs ou méthodes :

```php
use Xorg\GeolocatorBundle\Attribute\GeoFilter;

#[GeoFilter(
    blockedCountries: ['CN', 'RU'],
    allowedRanges: ['192.168.0.0/16'],
    requireNonVPN: true,
    simulate: false,
    pingThreshold: 5
)]
public function index(): Response
{
    // Si non autorisé, réponse 403/redirect
    return $this->render('home.html.twig');
}
```

Options disponibles :

- `blockedIps`, `blockedRanges`, `allowedRanges`
- `blockedCountries`, `allowedCountries`, `blockedContinents`, `allowedContinents`
- `blockedAsns`, `allowedAsns`, `blockedIsps`, `allowedIsps`
- `requireNonVPN`, `pingThreshold`, `simulate`, `forceProvider`
