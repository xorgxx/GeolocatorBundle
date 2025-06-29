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
    private bool $asyncEnabled;

    /**
     * @param MessageBusInterface $messageBus   Le bus Messenger pour dispatcher les messages.
     * @param GeolocationCache    $cache        Cache PSR-6 pour stocker les résultats.
     * @param LoggerInterface     $logger       Logger pour tracer les actions.
     * @param bool                $asyncEnabled Activation du mode asynchrone (RabbitMQ).
     */
    public function __construct(
        MessageBusInterface $messageBus,
        GeolocationCache $cache,
        LoggerInterface $logger,
        bool $asyncEnabled
    ) {
        $this->messageBus   = $messageBus;
        $this->cache        = $cache;
        $this->logger       = $logger;
        $this->asyncEnabled = $asyncEnabled;
    }

    /**
     * Lance une géolocalisation asynchrone si activé,
     * ou retourne immédiatement les données en cache si disponibles.
     *
     * @param string      $ip            Adresse IP à géolocaliser.
     * @param string|null $forceProvider Nom du provider à forcer (optionnel).
     *
     * @return array|null Tableau de données de géolocalisation, ou null si
     *                    async désactivé ou données non encore en cache.
     */
    public function geolocate(string $ip, ?string $forceProvider = null): ?array
    {
        // Si déjà en cache, on retourne directement
        if ($this->cache->has($ip)) {
            $this->logger->debug("Données de géolocalisation trouvées en cache pour l'IP {$ip}");
            return $this->cache->get($ip);
        }

        // Si l’asynchrone est désactivé, on retourne null
        if (!$this->asyncEnabled) {
            $this->logger->debug("Mode asynchrone désactivé, pas de dispatch de message pour l'IP {$ip}");
            return null;
        }

        // Génération d’un UUID pour traçabilité
        $requestId = Uuid::uuid4()->toString();
        $message   = new GeolocateMessage($ip, $forceProvider, $requestId);

        $this->logger->info(
            "Envoi d'une requête de géolocalisation asynchrone pour l'IP {$ip}",
            [
                'request_id'     => $requestId,
                'force_provider' => $forceProvider,
            ]
        );

        // Dispatch sécurisé
        try {
            $this->messageBus->dispatch($message);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Échec du dispatch du message de géolocalisation',
                [
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
