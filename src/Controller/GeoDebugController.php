<?php

namespace GeolocatorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GeoDebugController extends AbstractController
{
    #[Route('/__geo/debug', name: 'geo_debug')]
    public function debug(): Response
    {
        // TODO: afficher les infos de géolocalisation actuelles
        return new Response('Geo Debug info');
    }
}
