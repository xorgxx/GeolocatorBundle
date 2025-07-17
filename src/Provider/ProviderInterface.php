<?php

namespace GeolocatorBundle\Provider;

use GeolocatorBundle\Model\GeoLocation;

interface ProviderInterface
{
    /**
     * Retourne les informations de géolocalisation pour l'adresse IP spécifiée.
     */
    public function getGeoLocation(string $ip): GeoLocation;

    /**
     * Vérifie si ce provider peut détecter les VPNs.
     */
    public function supportsVpnDetection(): bool;

    /**
     * Vérifie si l'adresse IP est un VPN, Proxy ou Tor.
     * @throws \RuntimeException si le provider ne supporte pas la détection de VPN
     */
    public function isVpn(string $ip): bool;

    /**
     * Retourne le nom du provider.
     */
    public function getName(): string;
}
