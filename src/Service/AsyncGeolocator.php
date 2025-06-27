<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Message\GeolocateMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AsyncGeolocator
{
    private MessageBusInterface $messageBus;
    private GeolocationCache $cache;
    private LoggerInterface $logger;
    private bool $asyncEnabled;

    public function __construct(
        MessageBusInterface $messageBus,
        GeolocationCache $cache,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->messageBus = $messageBus;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->asyncEnabled = $params->get('geolocator.rabbit_enabled');
    }

    /**
     * Demande une géolocalisation de manière asynchrone si RabbitMQ est activé,
     * ou retourne immédiatement les données du cache si disponibles
     */
    public function geolocate(string $ip, ?string $forceProvider = null): ?array
    {
        // Si les données sont déjà en cache, les retourner immédiatement
        if ($this->cache->has($ip)) {
            $this->logger->debug("Données de géolocalisation trouvées en cache pour l'IP {$ip}");
            return $this->cache->get($ip);
        }

        // Si le mode asynchrone est désactivé, on renvoie null immédiatement
        // Le code appelant devra alors utiliser le ProviderManager directement
        if (!$this->asyncEnabled) {
            $this->logger->debug("Mode asynchrone désactivé, pas de dispatch de message pour l'IP {$ip}");
            return null;
        }

        // Création et envoi du message asynchrone
        $requestId = uniqid('geo_', true);
        $message = new GeolocateMessage($ip, $forceProvider, $requestId);

        $this->logger->info("Envoi d'une requête de géolocalisation asynchrone pour l'IP {$ip}", [
            'request_id' => $requestId,
            'force_provider' => $forceProvider
        ]);

        $this->messageBus->dispatch($message);

        // Retourne null car les données seront traitées de manière asynchrone
        return null;
    }

    /**
     * Vérifie si le mode asynchrone est activé
     */
    public function isAsyncEnabled(): bool
    {
        return $this->asyncEnabled;
    }
}
