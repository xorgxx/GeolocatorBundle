<?php
declare(strict_types=1);

namespace GeolocatorBundle\Message;

use Ramsey\Uuid\Uuid;

/**
 * Message pour la géolocalisation asynchrone d’une IP.
 */
final class GeolocateMessage
{
    private string $ip;
    private ?string $forceProvider;
    private string $requestId;

    /**
     * @param string      $ip            Adresse IP à géolocaliser.
     * @param string|null $forceProvider Nom du provider à forcer (optionnel).
     * @param string|null $requestId     Identifiant unique (généré si null).
     *
     * @throws \InvalidArgumentException Si l’IP est invalide.
     */
    public function __construct(string $ip, ?string $forceProvider = null, ?string $requestId = null)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException(sprintf('Adresse IP invalide : %s', $ip));
        }
        $this->ip            = $ip;
        $this->forceProvider = $forceProvider;
        $this->requestId     = $requestId ?? Uuid::uuid4()->toString();
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getForceProvider(): ?string
    {
        return $this->forceProvider;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
