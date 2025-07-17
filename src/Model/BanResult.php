<?php

namespace GeolocatorBundle\Model;

class BanResult
{
    private bool $isBanned;
    private string $reason;
    private string $ip;
    private ?GeoLocation $geoLocation = null;
    private ?\DateTimeInterface $bannedUntil = null;

    public function __construct(
        bool $isBanned, 
        string $reason, 
        string $ip = '', 
        ?GeoLocation $geoLocation = null,
        ?\DateTimeInterface $bannedUntil = null
    ) {
        $this->isBanned = $isBanned;
        $this->reason = $reason;
        $this->ip = $ip;
        $this->geoLocation = $geoLocation;
        $this->bannedUntil = $bannedUntil;
    }

    public function isBanned(): bool
    {
        return $this->isBanned;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getGeoLocation(): ?GeoLocation
    {
        return $this->geoLocation;
    }

    public function getBannedUntil(): ?\DateTimeInterface
    {
        return $this->bannedUntil;
    }

    public function setGeoLocation(?GeoLocation $geoLocation): self
    {
        $this->geoLocation = $geoLocation;
        return $this;
    }

    public function setBannedUntil(?\DateTimeInterface $bannedUntil): self
    {
        $this->bannedUntil = $bannedUntil;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'is_banned' => $this->isBanned,
            'reason' => $this->reason,
            'ip' => $this->ip,
            'geo_location' => $this->geoLocation ? $this->geoLocation->toArray() : null,
            'banned_until' => $this->bannedUntil ? $this->bannedUntil->format(\DateTimeInterface::ATOM) : null,
        ];
    }
}
