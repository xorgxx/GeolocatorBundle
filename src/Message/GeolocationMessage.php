<?php

namespace GeolocatorBundle\Message;

class GeolocationMessage
{
    private string $ip;
    private array $context;

    public function __construct(string $ip, array $context = [])
    {
        $this->ip = $ip;
        $this->context = $context;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

