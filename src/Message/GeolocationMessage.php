<?php

namespace GeolocatorBundle\Message;

/**
 * Message pour la géolocalisation asynchrone
 */
class GeolocationMessage
{
    private string $ip;

    public function __construct(string $ip)
    {
        $this->ip = $ip;
    }

    public function getIp(): string
    {
        return $this->ip;
    }
}
