<?php

namespace GeolocatorBundle\Controller;

use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de diagnostic pour le bundle Geolocator.
 */
class GeolocatorDebugController extends AbstractController
{
    private GeolocatorService $geolocator;
    private bool $enabled;
    private bool $simulate;
    private string $redirectOnBan;

    public function __construct(
        GeolocatorService $geolocator,
        bool $enabled,
        bool $simulate,
        string $redirectOnBan
    ) {
        $this->geolocator = $geolocator;
        $this->enabled = $enabled;
        $this->simulate = $simulate;
        $this->redirectOnBan = $redirectOnBan;
    }

    /**
     * Page de diagnostic pour le bundle Geolocator.
     */
    #[Route('/__geo/debug', name: 'geolocator_debug')]
    public function debug(Request $request): Response
    {
        // Vérifier si nous sommes en environnement de développement
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException('Cette page n\'est disponible qu\'en environnement de développement.');
        }

        // Vérifier le status du profiler
        $profilerEnabled = $this->getParameter('geolocator.profiler.enabled');

        // Vérifier le chargement du data collector
        $dataCollectorLoaded = class_exists('GeolocatorBundle\\DataCollector\\GeolocatorDataCollector');

        // Vérifier les services
        $serviceCheck = $this->has('geolocator.service');
        $dataCollectorCheck = $this->has('data_collector.geolocator');

        // Traiter la requête avec le service de géolocalisation
        $geoResult = $this->geolocator->processRequest($request);
        $geoLocation = $geoResult->getGeoLocation();

        return $this->render('@Geolocator/Debug/index.html.twig', [
            'enabled' => $this->enabled,
            'simulate' => $this->simulate,
            'redirect_on_ban' => $this->redirectOnBan,
            'ip' => $this->geolocator->getClientIp($request),
            'geoLocation' => $geoLocation,
            'is_banned' => $geoResult->isBanned(),
            'ban_reason' => $geoResult->getReason(),
            'profiler_enabled' => $profilerEnabled,
            'data_collector_loaded' => $dataCollectorLoaded,
            'service_check' => $serviceCheck,
            'data_collector_check' => $dataCollectorCheck,
        ]);
    }
}