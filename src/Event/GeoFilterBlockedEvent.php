<?php

namespace GeolocatorBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class GeoFilterBlockedEvent extends Event
{
    private string $ip;
    private string $reason;
    private ?string $country;

    public function __construct(string $ip, string $reason, ?string $country = null)
    {
        $this->ip = $ip;
        $this->reason = $reason;
        $this->country = $country;
    }

    public function getIp(): string { return $this->ip; }
    public function getReason(): string { return $this->reason; }
    public function getCountry(): ?string { return $this->country; }
}
