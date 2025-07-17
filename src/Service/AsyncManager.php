<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Message\GeolocationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class AsyncManager
{
    private bool $messengerEnabled;
    private bool $rabbitEnabled;
    private bool $redisEnabled;
    private bool $mercureEnabled;
    private ?MessageBusInterface $messageBus;
    private LoggerInterface $logger;

    public function __construct(
        bool $messengerEnabled = false,
        bool $rabbitEnabled = false,
        bool $redisEnabled = false,
        bool $mercureEnabled = false,
        ?MessageBusInterface $messageBus = null,
        LoggerInterface $logger
    ) {
        $this->messengerEnabled = $messengerEnabled;
        $this->rabbitEnabled = $rabbitEnabled;
        $this->redisEnabled = $redisEnabled;
        $this->mercureEnabled = $mercureEnabled;
        $this->messageBus = $messageBus;
        $this->logger = $logger;
    }

    /**
     * Détermine si le mode asynchrone est disponible
     */
    public function isAsyncAvailable(): bool
    {
        return $this->messengerEnabled && $this->messageBus !== null;
    }

    /**
     * Envoie une tâche de géolocalisation en traitement asynchrone
     */
    public function dispatchGeolocationTask(string $ip): bool
    {
        if (!$this->isAsyncAvailable()) {
            $this->logger->debug('Mode asynchrone non disponible, exécution synchrone');
            return false;
        }

        try {
            $message = new GeolocationMessage($ip);
            $this->messageBus->dispatch($message);
            $this->logger->info('Tâche de géolocalisation envoyée en asynchrone', ['ip' => $ip]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la tâche asynchrone: ' . $e->getMessage());
            return false;
        }
    }
}
