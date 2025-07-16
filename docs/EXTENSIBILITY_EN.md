# Extensibility

The bundle allows you to add your own filters using the `FilterInterface`.

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

Then, declare the service in `services.yaml`:

```yaml
services:
    App\Security\Filter\TimeWindowFilter:
        arguments: ['08:00', '18:00']
        tags:
            - { name: 'xorg.geofilter.filter' }
```

The `GeoFilterSubscriber` automatically executes all tagged services and applies the ban as soon as a filter returns `true`.