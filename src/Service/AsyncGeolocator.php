<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Message\GeolocateMessage;
use GeolocatorBundle\Service\GeolocationCache;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Gère l’envoi asynchrone des requêtes de géolocalisation via Messenger
 * et la lecture prioritaire du cache.
 */
final class AsyncGeolocator
{
    private MessageBusInterface $messageBus;
    private GeolocationCache $cache;
    private LoggerInterface $logger;
    private bool $transport;

    /**
     * @param MessageBusInterface $messageBus Messenger bus for dispatching messages.
     * @param GeolocationCache $cache PSR-6 cache for storing results.
     * @param LoggerInterface $logger Logger for tracing actions.
     * @param bool $transport Enable asynchronous mode (RabbitMQ).
     */
    public function __construct(
        MessageBusInterface $messageBus,
        GeolocationCache $cache,
        LoggerInterface $logger,
        bool $transport
    ) {
        $this->messageBus   = $messageBus;
        $this->cache        = $cache;
        $this->logger       = $logger;
        $this->asyncEnabled = $transport;
    }

    /**
     * Starts asynchronous geolocation if enabled,
     * or immediately returns cached data if available.
     *
     * @param string $ip IP address to geolocate.
     * @param string|null $forceProvider Provider name to force (optional).
     *
     * @return array|null Geolocation data array, or null if
     *                    async is disabled or data not yet cached.
     */
    public function geolocate(string $ip, ?string $forceProvider = null): ?array
    {
        // Si déjà en cache, on retourne directement
        if ($this->cache->has($ip)) {
            $this->logger->debug("Geolocation data found in cache for IP {$ip}");
            return $this->cache->get($ip);
        }

        // Si l’asynchrone est désactivé, on retourne null
        if (!$this->asyncEnabled) {
            $this->logger->debug("Async mode disabled, no message dispatch for IP {$ip}");
            return null;
        }

        // Génération d’un UUID pour traçabilité
        $requestId = Uuid::uuid4()->toString();
        $message   = new GeolocateMessage($ip, $forceProvider, $requestId);

        $this->logger->info("Sending asynchronous geolocation request for IP {$ip}", [
                'request_id'     => $requestId,
                'force_provider' => $forceProvider,
            ]
        );

        // Dispatch sécurisé
        try {
            $this->messageBus->dispatch($message);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to dispatch geolocation message', [
                    'exception'  => $e,
                    'request_id' => $requestId,
                ]
            );
        }

        // Les données arriveront ultérieurement en cache
        return null;
    }

    /**
     * Vérifie si le mode asynchrone est activé.
     */
    public function isAsyncEnabled(): bool
    {
        return $this->asyncEnabled;
    }
}
