<?php

namespace GeolocatorBundle\MessageHandler;

use GeolocatorBundle\Message\GeolocationMessage;
use GeolocatorBundle\Service\GeolocatorService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GeolocationMessageHandler
{
    private GeolocatorService $geolocator;
    private LoggerInterface $logger;

    public function __construct(GeolocatorService $geolocator, LoggerInterface $logger)
    {
        $this->geolocator = $geolocator;
        $this->logger = $logger;
    }

    public function __invoke(GeolocationMessage $message): void
    {
        $ip = $message->getIp();
        $this->logger->info('Traitement asynchrone de la géolocalisation', ['ip' => $ip]);

        try {
            // Obtenir la géolocalisation et mettre en cache
            $geoLocation = $this->geolocator->getGeoLocation($ip);
            $this->logger->info('Géolocalisation asynchrone réussie', [
                'ip' => $ip,
                'country' => $geoLocation->getCountryCode()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la géolocalisation asynchrone: ' . $e->getMessage(), ['ip' => $ip]);
        }
    }
}
