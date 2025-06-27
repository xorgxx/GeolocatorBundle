<?php

namespace GeolocatorBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GeolocatorController extends AbstractController
{
    #[Route('/admin/geolocator', name: 'admin_geolocator')]
    public function dashboard(): Response
    {
        // TODO: récupérer les données depuis la session
        return \$this->render('@XorgGeolocator/dashboard.html.twig', []);
    }
}
