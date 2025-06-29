<?php

// src/DataCollector/GeolocatorDataCollector.php
namespace GeolocatorBundle\DataCollector;

use GeolocatorBundle\Service\AsyncGeolocator;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GeolocatorDataCollector extends DataCollector
{
    private AsyncGeolocator $asyncLocator;

    public function __construct(AsyncGeolocator $asyncLocator)
    {
        $this->asyncLocator = $asyncLocator;
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $this->data = [
            'async_enabled' => $this->asyncLocator->isAsyncEnabled(),
            // tu peux ajouter d’autres infos (cache hit, provider courant…)
        ];
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getName(): string
    {
        return 'geolocator';
    }

    public function isAsyncEnabled(): bool
    {
        return $this->data[ 'async_enabled' ] ?? false;
    }
}
