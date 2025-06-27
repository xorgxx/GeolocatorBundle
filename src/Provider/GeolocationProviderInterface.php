<?php

namespace GeolocatorBundle\Provider;

interface GeolocationProviderInterface
{
    public function getName(): string;
    public function locate(string $ip): array;
    public function isAvailable(): bool;
}
