<?php

namespace GeolocatorBundle\Controller;

use GeolocatorBundle\Attribute\GeoConfig;
use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour le débogage et les tests de géolocalisation
 */
#[Route('/geolocator')]
class GeolocatorDebugController extends BaseGeolocatorController
{
    /**
     * Page de debug pour visualiser toute la configuration
     */
    #[Route('/debug', name: 'geolocator_debug')]
    public function debug(Request $request): Response {
        $defaultProvider = $this->getParameter('geolocator.providers.default');
        // Vérifier si l'utilisateur est en environnement de développement
        if ($this->getParameter('kernel.environment') !== 'dev' && !$request->query->has('force')) {
            throw $this->createAccessDeniedException('Cette page n\'est accessible qu\'en environnement de développement');
        }

        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent');

        try {
            $geoLocation = $this->geolocator->getGeoLocation($ip);

            return $this->render('@Geolocator/debug.html.twig', [
                'ip' => $ip,
                'userAgent' => $userAgent,
                'geoLocation' => $geoLocation,
                'enabled' => $this->enabled,
                'simulate' => $this->simulate,
                'defaultProvider' => $defaultProvider,
                'isBanned' => $this->geolocator->isBanned($ip),
                'redirectOnBan' => $this->redirectOnBan,
                'vpnDetection' => $this->getConfig('geolocator.vpn_detection.enabled', false),
                'countryFilters' => [
                    'allowed' => $this->getConfig('geolocator.country_filters.allow', []),
                    'blocked' => $this->getConfig('geolocator.country_filters.block', [])
                ]
            ]);
        } catch (\Exception $e) {
            return $this->render('@Geolocator/error.html.twig', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * API pour tester la géolocalisation d'une IP spécifique
     */
    #[Route('/api/test/{ip}', name: 'geolocator_api_test', defaults: ['ip' => null])]
    public function apiTest(?string $ip, Request $request): JsonResponse
    {
        // Utiliser l'IP fournie ou celle du client
        $ip = $ip ?: $request->getClientIp();

        try {
            $geoLocation = $this->geolocator->getGeoLocation($ip);
            $isAllowed = $this->geolocator->isCountryAllowed($geoLocation->getCountryCode());

            return $this->json([
                'success' => true,
                'ip' => $geoLocation->getIp(),
                'country' => [
                    'code' => $geoLocation->getCountryCode(),
                    'name' => $geoLocation->getCountryName(),
                    'allowed' => $isAllowed
                ],
                'location' => [
                    'region' => $geoLocation->getRegionName(),
                    'city' => $geoLocation->getCity(),
                    'latitude' => $geoLocation->getLatitude(),
                    'longitude' => $geoLocation->getLongitude(),
                    'timezone' => $geoLocation->getTimezone()
                ],
                'network' => [
                    'vpn' => $geoLocation->isVpn(),
                    'proxy' => $geoLocation->isProxy(),
                    'tor' => $geoLocation->isTor(),
                    'isp' => $geoLocation->getIsp(),
                    'org' => $geoLocation->getOrg(),
                    'asn' => $geoLocation->getAsn()
                ],
                'config' => [
                    'enabled' => $this->enabled,
                    'simulate' => $this->simulate,
                    'vpnDetection' => $this->getConfig('geolocator.vpn_detection.enabled'),
                    'banTtl' => $this->getConfig('geolocator.bans.ttl')
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
