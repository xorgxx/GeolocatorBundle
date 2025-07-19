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
    private array $ignoredRoutes;

    public function __construct(GeolocatorService $geolocator, string $redirectUrl, bool $enabled, bool $simulate, array $ignoredRoutes = [])
    {
        $this->geolocator = $geolocator;
        $this->redirectUrl = $redirectUrl;
        $this->enabled = $enabled;
        $this->simulate = $simulate;
        $this->ignoredRoutes = $ignoredRoutes;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ne pas traiter les requêtes vers la page de ban pour éviter les boucles infinies
        if ($request->getPathInfo() === $this->redirectUrl) {
            return;
        }

        // Vérifier si la route est dans la liste des routes ignorées
        if ($route && $this->isRouteIgnored($route)) {
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

    /**
     * Vérifie si une route doit être ignorée selon les patterns configurés
     */
    private function isRouteIgnored(string $route): bool
    {
        foreach ($this->ignoredRoutes as $pattern) {
            // Support des jokers (*) dans les patterns
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('*', '.*', $pattern) . '$/i';
                if (preg_match($regex, $route)) {
                    return true;
                }
            } elseif ($route === $pattern) {
                return true;
            }
        }

        return false;
    }
}
