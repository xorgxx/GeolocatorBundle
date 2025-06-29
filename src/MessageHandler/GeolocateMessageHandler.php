<?php
declare(strict_types=1);

namespace GeolocatorBundle\MessageHandler;

use GeolocatorBundle\Message\GeolocateMessage;
use GeolocatorBundle\Provider\GeolocationProviderInterface;
use GeolocatorBundle\Service\ProviderManager;
use GeolocatorBundle\Service\GeolocationCache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Process a GeolocateMessage in background:
 *   - select a provider (optionally forced)
 *   - retrieve data
 *   - store in cache
 */
#[AsMessageHandler]
final class GeolocateMessageHandler
{
    private ProviderManager  $providerManager;
    private GeolocationCache $cache;
    private LoggerInterface  $logger;

    public function __construct(
        ProviderManager $providerManager,
        GeolocationCache $cache,
        LoggerInterface $logger
    ) {
        $this->providerManager = $providerManager;
        $this->cache           = $cache;
        $this->logger          = $logger;
    }

    public function __invoke(GeolocateMessage $message): void
    {
        $ip        = $message->getIp();
        $requestId = $message->getRequestId();

        $this->logger->info('Starting asynchronous geolocation', [
            'request_id' => $requestId,
            'ip'         => $ip,
        ]);

        try {
            if ($this->cache->has($ip)) {
                $this->logger->info('Data already in cache, skipping', [
                    'request_id' => $requestId,
                    'ip'         => $ip,
                ]);
                return;
            }

            // Choix du provider (forcé ou non)
            $provider = $message->getForceProvider() !== null
                ? $this->providerManager->getProviderByName($message->getForceProvider())
                : $this->providerManager->getNextProvider();

            $geoData = $provider->locate($ip);

            $this->cache->set($ip, $geoData);

            $this->logger->info('Geolocation successful', [
                'request_id' => $requestId,
                'ip'         => $ip,
                'provider'   => $provider->getName(),
                'country'    => $geoData['country'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Asynchronous geolocation error', [
                'request_id' => $requestId,
                'ip'         => $ip,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
