<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;

class ProviderManager
{
    /** @var GeolocationProviderInterface[] */
    private array $providers;
    private int   $currentIndex = 0;
    private const NO_PROVIDERS_CONFIGURED_MESSAGE = 'No geolocation providers configured.';

    public function __construct(iterable $geolocationProviders)
    {
        $this->providers = iterator_to_array($geolocationProviders);
    }

    public function getNextProvider(): GeolocationProviderInterface
    {
        if (empty($this->providers)) {
            throw new \RuntimeException(self::NO_PROVIDERS_CONFIGURED_MESSAGE);
        }
        $provider = $this->selectProviderByIndex($this->currentIndex);
        $this->currentIndex++;
        return $provider;
    }

    private function selectProviderByIndex(int $index): GeolocationProviderInterface
    {
        return $this->providers[ $index % count($this->providers) ];
    }
}
