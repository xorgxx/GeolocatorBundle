<?php

namespace GeolocatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfilerTestController extends AbstractController
{
    #[Route('/__geo/profiler-test', name: 'geolocator_profiler_test')]
    public function index(): Response
    {
        return new Response(
            '<html><body>'
            . '<h1>Test du Profiler Geolocator</h1>'
            . '<p>Cette page sert à tester l\'intégration du profiler.</p>'
            . '<p>Vérifiez la barre de débogage en bas de la page pour voir si l\'icône Geolocator est présente.</p>'
            . '</body></html>'
        );
    }
}
