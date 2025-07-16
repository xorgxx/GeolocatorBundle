# Extensibilité

Le bundle permet d'ajouter vos propres filtres à l'aide de l'interface `FilterInterface`.

```php
namespace App\Security\Filter;

use Symfony\Component\HttpFoundation\Request;
use Xorg\GeolocatorBundle\Service\FilterInterface;

class TimeWindowFilter implements FilterInterface
{
    public function __construct(private string $start, private string $end) {}

    public function apply(Request $request, array $geoData): bool
    {
        $now = new \DateTime();
        return !($now->format('H:i') >= $this->start && $now->format('H:i') <= $this->end);
    }
}
```

Ensuite, déclarez le service dans `services.yaml` :

```yaml
services:
    App\Security\Filter\TimeWindowFilter:
        arguments: ['08:00', '18:00']
        tags:
            - { name: 'xorg.geofilter.filter' }
```

Le subscriber `GeoFilterSubscriber` exécute automatiquement tous les services taggés et applique le ban dès qu'un filtre renvoie `true`.