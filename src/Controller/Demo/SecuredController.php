<?php

namespace GeolocatorBundle\Controller\Demo;

use GeolocatorBundle\Attribute\GeoFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Exemple de contrôleur utilisant l'attribut GeoFilter.
 * 
 * @Route("/demo")
 */
#[GeoFilter(allowedCountries: ['FR', 'BE', 'CH', 'LU'], blockVpn: true)]
class SecuredController extends AbstractController
{
    /**
     * Cette action hérite des règles GeoFilter de la classe.
     * 
     * @Route("/secured-action", name="demo_secured_action")
     */
    public function securedAction(): Response
    {
        return new Response(
            '<html><body><h1>Action sécurisée</h1><p>Vous êtes autorisé à accéder à cette page car vous répondez aux critères de filtrage.</p></body></html>'
        );
    }

    /**
     * Cette action a ses propres règles qui remplacent celles de la classe.
     * 
     * @Route("/very-secured-action", name="demo_very_secured_action")
     */
    #[GeoFilter(allowedCountries: ['FR'], allowedRanges: ['127.0.0.1/32'], blockCrawlers: true)]
    public function verySecuredAction(): Response
    {
        return new Response(
            '<html><body><h1>Action très sécurisée</h1><p>Vous êtes autorisé à accéder à cette page car vous répondez à des critères de filtrage stricts.</p></body></html>'
        );
    }

    /**
     * Cette action n'a pas de règles GeoFilter spécifiques.
     * 
     * @Route("/open-action", name="demo_open_action")
     */
    public function openAction(): Response
    {
        return new Response(
            '<html><body><h1>Action ouverte</h1><p>Cette action n\'applique pas les règles GeoFilter de la classe car elle est annotée pour les ignorer.</p></body></html>'
        );
    }
}
