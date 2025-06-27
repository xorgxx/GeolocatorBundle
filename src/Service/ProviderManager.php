<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;

class ProviderManager
{
    /** @var GeolocationProviderInterface[] */
    private array $providers;
    private int $index = 0;

    public function __construct(iterable $providers)
    {
        $this->providers = iterator_to_array($providers);
    }

    public function getNextProvider(): GeolocationProviderInterface
    {
        if (empty($this->providers)) {
            throw new \RuntimeException('No geolocation providers configured.');
        }
        $provider = $this->providers[$this->index % count($this->providers)];
        $this->index++;
        return $provider;
    }
}
