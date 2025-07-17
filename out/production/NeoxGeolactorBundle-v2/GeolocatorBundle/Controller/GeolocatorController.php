<?php

namespace GeolocatorBundle\Controller;

use GeolocatorBundle\Service\BanManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GeolocatorController extends AbstractController
{
    /**
     * Page affichée lorsqu'un utilisateur est banni.
     * @Route("/banned", name="geolocator_banned")
     */
    public function bannedAction(Request $request, BanManager $banManager): Response
    {
        $ip = $request->getClientIp();
        $banInfo = $banManager->getBanInfo($ip);

        $params = [
            'ip' => $ip,
            'ban_info' => $banInfo,
            'is_permanent' => $banInfo && $banInfo['expiration'] === null,
            'expiration' => $banInfo && $banInfo['expiration'] ? new \DateTime($banInfo['expiration']) : null,
        ];

        return $this->render('@Geolocator/banned.html.twig', $params);
    }
}
