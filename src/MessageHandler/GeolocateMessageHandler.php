<?php

namespace GeolocatorBundle\MessageHandler;

use GeolocatorBundle\Message\GeolocateMessage;
use GeolocatorBundle\Service\ProviderManager;
use GeolocatorBundle\Service\GeolocationCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GeolocateMessageHandler
{
    private ProviderManager $providerManager;
    private GeolocationCache $cache;
    private LoggerInterface $logger;

    public function __construct(
        ProviderManager $providerManager,
        GeolocationCache $cache,
        LoggerInterface $logger
    ) {
        $this->providerManager = $providerManager;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function __invoke(GeolocateMessage $message): void
    {
        try {
            $ip = $message->getIp();
            $requestId = $message->getRequestId();
            $this->logger->info("Traitement asynchrone de la géolocalisation pour l'IP {$ip}", [
                'request_id' => $requestId
            ]);

            // Si déjà dans le cache, ne pas retraiter
            if ($this->cache->has($ip)) {
                $this->logger->info("IP {$ip} déjà en cache, traitement asynchrone ignoré", [
                    'request_id' => $requestId
                ]);
                return;
            }

            // Sélection du provider (forcé ou via round-robin)
            if ($message->getForceProvider()) {
                // Logique pour sélectionner un provider spécifique serait à implémenter
                $provider = $this->providerManager->getNextProvider();
            } else {
                $provider = $this->providerManager->getNextProvider();
            }

            // Obtention des données de géolocalisation
            $geoData = $provider->locate($ip);

            // Mise en cache des résultats
            $this->cache->set($ip, $geoData);

            $this->logger->info("Géolocalisation asynchrone réussie pour l'IP {$ip}", [
                'request_id' => $requestId,
                'provider' => $provider->getName(),
                'country' => $geoData['country'] ?? 'unknown'
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("Erreur lors de la géolocalisation asynchrone: {$e->getMessage()}", [
                'request_id' => $message->getRequestId(),
                'ip' => $message->getIp(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
