<?php

namespace GeolocatorBundle\Controller;

use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/geo', name: 'geolocator_api_')]
class GeoApiController extends AbstractController
{
    private GeolocatorService $geolocator;
    private BanManager $banManager;

    public function __construct(GeolocatorService $geolocator, BanManager $banManager)
    {
        $this->geolocator = $geolocator;
        $this->banManager = $banManager;
    }

    /**
     * Récupérer les informations de géolocalisation pour une IP
     */
    #[Route('/locate/{ip}', name: 'locate', methods: ['GET'])]
    public function locateIp(string $ip): JsonResponse
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->json(['error' => 'Adresse IP non valide'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->geolocator->getGeoLocation($ip);

        return $this->json($result);
    }

    /**
     * Récupérer les informations de géolocalisation pour l'IP du client
     */
    #[Route('/me', name: 'locate_me', methods: ['GET'])]
    public function locateClient(Request $request): JsonResponse
    {
        $ip = $request->getClientIp();
        $result = $this->geolocator->getGeoLocation($ip);

        return $this->json($result);
    }

    /**
     * Vérifier si une IP est bannie
     */
    #[Route('/check-ban/{ip}', name: 'check_ban', methods: ['GET'])]
    public function checkBan(string $ip): JsonResponse
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->json(['error' => 'Adresse IP non valide'], Response::HTTP_BAD_REQUEST);
        }

        $isBanned = $this->banManager->isBanned($ip);
        $banInfo = $isBanned ? $this->banManager->getBanInfo($ip) : null;

        return $this->json([
            'ip' => $ip,
            'banned' => $isBanned,
            'ban_info' => $banInfo
        ]);
    }

    /**
     * Vérifier les informations IP et pays par rapport aux règles de filtrage
     */
    #[Route('/check', name: 'check', methods: ['GET'])]
    public function checkRules(Request $request): JsonResponse
    {
        $ip = $request->query->get('ip') ?: $request->getClientIp();
        $country = $request->query->get('country');
        $allowedCountries = $request->query->all('allowed_countries');
        $blockedCountries = $request->query->all('blocked_countries');

        // Si aucun pays n'est spécifié, utiliser la géolocalisation
        if (!$country) {
            $geoInfo = $this->geolocator->getGeoLocation($ip);
            $country = $geoInfo['country_code'] ?? null;
        }

        $result = [
            'ip' => $ip,
            'country' => $country,
        ];

        // Vérifier si l'IP est bannie
        $result['is_banned'] = $this->banManager->isBanned($ip);

        // Vérifier le pays par rapport aux listes autorisées/bloquées
        if ($country && !empty($allowedCountries)) {
            $result['country_allowed'] = in_array($country, $allowedCountries);
        }

        if ($country && !empty($blockedCountries)) {
            $result['country_blocked'] = in_array($country, $blockedCountries);
        }

        return $this->json($result);
    }
}
