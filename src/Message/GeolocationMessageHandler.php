<?php

namespace GeolocatorBundle\Message;

use GeolocatorBundle\Service\AsyncGeolocator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Handler pour traiter les messages de géolocalisation asynchrone
 */
class GeolocationMessageHandler implements MessageHandlerInterface
{
    private AsyncGeolocator $asyncGeolocator;

    public function __construct(AsyncGeolocator $asyncGeolocator)
    {
        $this->asyncGeolocator = $asyncGeolocator;
    }

    public function __invoke(GeolocationMessage $message)
    {
        $this->asyncGeolocator->processGeoLocation($message->getIp());
    }
}
