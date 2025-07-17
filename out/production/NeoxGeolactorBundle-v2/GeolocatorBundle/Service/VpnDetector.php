<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Model\GeoLocation;

class VpnDetector
{
    private array $config;
    private array $providers;
    private array $allowedIps;

    public function __construct(array $config, array $providers = [], array $allowedIps = [])
    {
        $this->config = $config;
        $this->providers = $providers;
        $this->allowedIps = $allowedIps;
    }

    /**
     * Vérifie si une IP utilise un VPN/proxy/Tor.
     */
    public function isVpn(string $ip, ?GeoLocation $geoLocation = null): bool
    {
        if (!$this->config['enabled']) {
            return false;
        }

        // Si l'IP est dans la liste des IPs autorisées, on la laisse passer
        if (in_array($ip, $this->allowedIps)) {
            return false;
        }

        // Si on a déjà les informations de géolocalisation avec les données VPN
        if ($geoLocation !== null) {
            return $this->checkVpnFromGeoLocation($geoLocation);
        }

        // Sinon, on utilise le provider configuré pour détecter les VPNs
        $providerName = $this->config['provider'] ?? 'ipqualityscore';

        if (isset($this->providers[$providerName])) {
            $provider = $this->providers[$providerName];

            if ($provider->supportsVpnDetection()) {
                try {
                    return $provider->isVpn($ip);
                } catch (\Exception $e) {
                    // En cas d'erreur, on considère que ce n'est pas un VPN par sécurité
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Vérifie si un GeoLocation contient des informations indiquant un VPN/proxy/Tor.
     */
    private function checkVpnFromGeoLocation(GeoLocation $geoLocation): bool
    {
        return $geoLocation->isVpn() === true || 
               $geoLocation->isProxy() === true || 
               $geoLocation->isTor() === true;
    }
}
