<?php

namespace GeolocatorBundle\DataCollector;

use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class GeolocatorDataCollector extends DataCollector
{
    private GeolocatorService $geolocator;
    private bool $enabled;

    public function __construct(GeolocatorService $geolocator, bool $enabled = true)
    {
        $this->geolocator = $geolocator;
        $this->enabled = $enabled;
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        if (!$this->enabled) {
            $this->data = [
                'enabled' => false,
                'ip' => null,
                'geoLocation' => null,
                'error' => null,
            ];
            return;
        }

        try {
            // Récupérer les informations de géolocalisation de la requête actuelle
            $geoLocation = $this->geolocator->getGeoLocationFromRequest($request);

            // Obtenir l'IP du client
            $ip = $this->geolocator->getClientIp($request);

            // Vérifier si l'IP est bloquée
            $banInfo = $this->geolocator->getBanManager()->getBanInfo($ip);

            // Préparer les données de géolocalisation pour le template
            $geoLocationData = null;
            if ($geoLocation) {
                $geoLocationData = [
                    'country_code' => $geoLocation->getCountryCode(),
                    'country_name' => $geoLocation->getCountryName(),
                    'city' => $geoLocation->getCity(),
                    'latitude' => $geoLocation->getLatitude(),
                    'longitude' => $geoLocation->getLongitude(),
                    'is_vpn' => $geoLocation->isVpn(),
                    'provider' => $geoLocation->getProvider(),
                    'timezone' => $geoLocation->getTimezone(),
                    'region' => $geoLocation->getRegion(),
                    'isp' => $geoLocation->getIsp(),
                ];
            }

            // Collecter les données pour le profiler
            $this->data = [
                'enabled' => $this->enabled,
                'ip' => $ip,
                'geoLocation' => $geoLocationData,
                'country' => $geoLocation ? $geoLocation->getCountryCode() : null,
                'country_name' => $geoLocation ? $geoLocation->getCountryName() : null,
                'city' => $geoLocation ? $geoLocation->getCity() : null,
                'latitude' => $geoLocation ? $geoLocation->getLatitude() : null,
                'longitude' => $geoLocation ? $geoLocation->getLongitude() : null,
                'is_banned' => $banInfo !== null,
                'ban_info' => $banInfo,
                'is_vpn' => $geoLocation ? $geoLocation->isVpn() : false,
                'is_crawler' => $request->headers->has('User-Agent') && $this->geolocator->getCrawlerFilter()->isBot($request->headers->get('User-Agent')),
                'provider_used' => $geoLocation ? $geoLocation->getProvider() : null,
                'simulation_mode' => $this->geolocator->isSimulationMode(),
                'async_available' => $this->geolocator->isAsyncAvailable(),
                'error' => null,
                'ip_filter' => [
                    'in_allow_list' => $this->geolocator->getIpFilter()->isInAllowList($ip),
                    'in_block_list' => $this->geolocator->getIpFilter()->isInBlockList($ip),
                    'is_allowed' => $this->geolocator->isIpAllowed($ip),
                    'config' => $this->geolocator->getIpFilter()->getConfig(),
                ],
            ];
        } catch (\Throwable $e) {
            $this->data = [
                'enabled' => $this->enabled,
                'ip' => null,
                'geoLocation' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'geolocator';
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function isEnabled(): bool
    {
        return $this->data['enabled'] ?? false;
    }

    public function getIp(): ?string
    {
        return $this->data['ip'] ?? null;
    }

    public function getGeoLocation(): ?array
    {
        return $this->data['geoLocation'] ?? null;
    }

    public function getError(): ?string
    {
        return $this->data['error'] ?? null;
    }

    public function getCountry(): ?string
    {
        return $this->data['country'] ?? null;
    }

    public function getCountryName(): ?string
    {
        return $this->data['country_name'] ?? null;
    }

    public function getCity(): ?string
    {
        return $this->data['city'] ?? null;
    }

    public function getCoordinates(): array
    {
        return [
            'latitude' => $this->data['latitude'] ?? null,
            'longitude' => $this->data['longitude'] ?? null,
        ];
    }

    public function isBanned(): bool
    {
        return $this->data['is_banned'] ?? false;
    }

    public function getBanInfo(): ?array
    {
        return $this->data['ban_info'] ?? null;
    }

    public function isVpn(): bool
    {
        return $this->data['is_vpn'] ?? false;
    }

    public function isCrawler(): bool
    {
        return $this->data['is_crawler'] ?? false;
    }

    public function getProviderUsed(): ?string
    {
        return $this->data['provider_used'] ?? null;
    }

    public function isSimulationMode(): bool
    {
        return $this->data['simulation_mode'] ?? false;
    }

    public function isAsyncAvailable(): bool
    {
        return $this->data['async_available'] ?? false;
    }
}