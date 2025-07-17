<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Message\GeolocationMessage;
use GeolocatorBundle\Model\GeoLocation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Service pour la géolocalisation asynchrone des adresses IP
 */
class AsyncGeolocator
{
    private GeolocatorService $geolocator;
    private AsyncManager $asyncManager;
    private LoggerInterface $logger;

    public function __construct(
        GeolocatorService $geolocator,
        AsyncManager $asyncManager,
        LoggerInterface $logger
    ) {
        $this->geolocator = $geolocator;
        $this->asyncManager = $asyncManager;
        $this->logger = $logger;
    }

    /**
     * Demande la géolocalisation d'une adresse IP de manière asynchrone
     */
    public function requestGeoLocation(string $ip): bool
    {
        if (!$this->asyncManager->isAsyncAvailable()) {
            $this->logger->debug('Mode asynchrone non disponible, exécution synchrone');
            return false;
        }

        return $this->asyncManager->dispatchGeolocationTask($ip);
    }

    /**
     * Traite une demande de géolocalisation (appelé par le message handler)
     */
    public function processGeoLocation(string $ip): ?GeoLocation
    {
        try {
            $result = $this->geolocator->getGeoLocation($ip);
            $this->logger->info('Géolocalisation asynchrone réussie', ['ip' => $ip]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la géolocalisation asynchrone: ' . $e->getMessage(), ['ip' => $ip]);
            return null;
        }
    }
}
