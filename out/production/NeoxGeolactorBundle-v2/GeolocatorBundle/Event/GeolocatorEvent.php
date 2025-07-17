<?php

namespace GeolocatorBundle\Event;

use GeolocatorBundle\Model\BanResult;
use Symfony\Contracts\EventDispatcher\Event;

class GeolocatorEvent extends Event
{
    private BanResult $result;

    public function __construct(BanResult $result)
    {
        $this->result = $result;
    }

    public function getResult(): BanResult
    {
        return $this->result;
    }

    public function isBanned(): bool
    {
        return $this->result->isBanned();
    }

    public function getReason(): string
    {
        return $this->result->getReason();
    }

    public function getIp(): string
    {
        return $this->result->getIp();
    }
}
