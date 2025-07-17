<?php

namespace GeolocatorBundle\EventListener;

use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class GeolocatorListener
{
    private GeolocatorService $geolocator;
    private string $redirectUrl;
    private bool $enabled;
    private bool $simulate;

    public function __construct(GeolocatorService $geolocator, string $redirectUrl, bool $enabled, bool $simulate)
    {
        $this->geolocator = $geolocator;
        $this->redirectUrl = $redirectUrl;
        $this->enabled = $enabled;
        $this->simulate = $simulate;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Ne pas traiter les requêtes vers la page de ban pour éviter les boucles infinies
        if ($request->getPathInfo() === $this->redirectUrl) {
            return;
        }

        // Traiter la requête
        $result = $this->geolocator->processRequest($request);

        // Si la requête est bannie et que nous ne sommes pas en mode simulation,
        // rediriger vers la page de ban
        if ($result->isBanned() && !$this->simulate) {
            $response = new RedirectResponse($this->redirectUrl);
            $event->setResponse($response);
        }
    }
}
