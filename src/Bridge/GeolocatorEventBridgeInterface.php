<?php
namespace GeolocatorBundle\Bridge;

interface GeolocatorEventBridgeInterface
{
    /**
     * Handle an event dispatched by GeolocatorBundle.
     *
     * @param array $payload
     * @param string $eventType (ex: ban, block, simulate, log, ...)
     */
    public function notify(array $payload, string $eventType): void;
}
