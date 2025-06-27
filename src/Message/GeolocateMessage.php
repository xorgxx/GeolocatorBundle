<?php

namespace GeolocatorBundle\Message;

class GeolocateMessage
{
    private string $ip;
    private ?string $forceProvider;
    private ?string $requestId;

    public function __construct(string $ip, ?string $forceProvider = null, ?string $requestId = null)
    {
        $this->ip = $ip;
        $this->forceProvider = $forceProvider;
        $this->requestId = $requestId ?: uniqid('geo_', true);
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
