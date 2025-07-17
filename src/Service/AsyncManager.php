<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Message\GeolocationMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

class AsyncManager
{
    private bool $asyncEnabled;
    private bool $messengerEnabled;
    private bool $rabbitEnabled;
    private bool $redisEnabled;
    private bool $mercureEnabled;
    private ?MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private string $transportName;

    public function __construct(
        bool $asyncEnabled = false,
        bool $messengerEnabled = false,
        bool $rabbitEnabled = false,
        bool $redisEnabled = false,
        bool $mercureEnabled = false,
        ?MessageBusInterface $messageBus = null,
        LoggerInterface $logger,
        string $transportName = 'async'
    ) {
        $this->asyncEnabled = $asyncEnabled;
        $this->messengerEnabled = $messengerEnabled;
        $this->rabbitEnabled = $rabbitEnabled;
        $this->redisEnabled = $redisEnabled;
        $this->mercureEnabled = $mercureEnabled;
        $this->messageBus = $messageBus;
        $this->logger = $logger;
        $this->transportName = $transportName;
    }

    /**
     * Détermine si le mode asynchrone est disponible et activé
     */
    public function isAsyncAvailable(): bool
    {
        return $this->asyncEnabled && $this->messengerEnabled && $this->messageBus !== null;
    }

    /**
     * Active ou désactive le mode asynchrone
     */
    public function setAsyncEnabled(bool $enabled): void
    {
        $this->asyncEnabled = $enabled;
    }

    /**
     * Active ou désactive le Messenger
     */
    public function setMessengerEnabled(bool $enabled): void
    {
        $this->messengerEnabled = $enabled && $this->messageBus !== null;
    }

    /**
     * Définit le nom du transport à utiliser
     */
    public function setTransportName(string $transportName): void
    {
        $this->transportName = $transportName;
    }

    /**
     * Envoie une tâche de géolocalisation en traitement asynchrone
     * 
     * @param string $ip L'adresse IP à géolocaliser
     * @param array $context Contexte additionnel à passer au handler
     * @return bool True si la tâche a été envoyée avec succès, false sinon
     */
    public function dispatchGeolocationTask(string $ip, array $context = []): bool
    {
        if (!$this->isAsyncAvailable()) {
            $this->logger->debug('Mode asynchrone non disponible ou désactivé, exécution synchrone', [
                'ip' => $ip,
                'async_enabled' => $this->asyncEnabled,
                'messenger_enabled' => $this->messengerEnabled,
                'messageBus_available' => $this->messageBus !== null
            ]);
            return false;
        }

        try {
            $message = new GeolocationMessage($ip, $context);
            $this->messageBus->dispatch($message);
            $this->logger->info('Tâche de géolocalisation envoyée en asynchrone', [
                'ip' => $ip, 
                'transport' => $this->transportName
            ]);
            return true;
        } catch (HandlerFailedException $e) {
            // Extraire l'exception d'origine
            $previous = $e->getPrevious() ?? $e;
            $this->logger->error('Erreur lors du traitement de la tâche asynchrone: ' . $previous->getMessage(), [
                'ip' => $ip,
                'exception' => get_class($previous)
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la tâche asynchrone: ' . $e->getMessage(), [
                'ip' => $ip,
                'exception' => get_class($e)
            ]);
            return false;
        }
    }

    /**
     * Vérifie si un transport spécifique est disponible
     */
    public function isTransportAvailable(string $type): bool
    {
        switch ($type) {
            case 'rabbit':
                return $this->rabbitEnabled;
            case 'redis':
                return $this->redisEnabled;
            case 'mercure':
                return $this->mercureEnabled;
            case 'messenger':
                return $this->messengerEnabled;
            default:
                return false;
        }
    }
}
