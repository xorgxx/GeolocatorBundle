<?php

namespace GeolocatorBundle\Controller;

use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GeolocatorController extends AbstractController
{
    private GeolocatorService $geolocator;
    private BanManager        $banManager;

    public function __construct(
        GeolocatorService $geolocator,
        BanManager        $banManager,

    ) {
        $this->geolocator = $geolocator;
        $this->banManager= $banManager;
    }

    /**
     * Page affichée lorsqu'un utilisateur est banni.
     */
    #[Route('/banned', name: 'geolocator_banned')]
    public function bannedAction(Request $request): Response
    {
        $ip = $request->getClientIp();
        $banInfo = $this->banManager->getBanInfo($ip);

        $params = [
            'ip' => $ip,
            'ban_info' => $banInfo,
            'is_permanent' => $banInfo && $banInfo['expiration'] === null,
            'expiration' => $banInfo && $banInfo['expiration'] ? new \DateTime($banInfo['expiration']) : null,
        ];

        return $this->render('@Geolocator/banned.html.twig', $params);
    }
}
