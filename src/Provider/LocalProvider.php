<?php

namespace GeolocatorBundle\Provider;

use GeolocatorBundle\Model\GeoLocation;

/**
 * Provider local qui fournit des informations de base sans API externe.
 * Utilisé comme solution de secours quand aucun provider externe n'est disponible.
 */
class LocalProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    public function getGeoLocation(string $ip): GeoLocation
    {
        // Information de base sans API externe
        $data = [
            'ip' => $ip,
            'country_code' => 'XX', // Pays inconnu
            'country_name' => 'Unknown',
            'region' => 'Unknown',
            'city' => 'Unknown',
            'latitude' => 0,
            'longitude' => 0,
            'is_local' => $this->isLocalIp($ip),
            'provider' => 'local',
        ];

        // Détecter les IPs locales et leur assigner FR comme pays par défaut
        if ($this->isLocalIp($ip)) {
            $data['country_code'] = 'FR';
            $data['country_name'] = 'France';
        }

        return new GeoLocation($ip, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'local';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsVpnDetection(): bool
    {
        return false;
    }

    /**
     * Vérifie si une adresse IP est locale (réseau privé, localhost, etc.)
     */
    private function isLocalIp(string $ip): bool
    {
        // IPs locales
        $localRanges = [
            '127.0.0.0/8',    // localhost
            '10.0.0.0/8',      // Classe A privée
            '172.16.0.0/12',   // Classe B privée
            '192.168.0.0/16',  // Classe C privée
            '169.254.0.0/16',  // APIPA
            '::1',             // localhost IPv6
            'fc00::/7',        // IPv6 unique local
            'fe80::/10',       // IPv6 link-local
        ];

        foreach ($localRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si une IP est dans une plage CIDR
     */
    private function ipInRange(string $ip, string $range): bool
    {
        // Simplification pour IPv4 uniquement
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($subnet, $bits) = explode('/', $range);

        // Pour IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && 
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $mask = -1 << (32 - (int)$bits);
            $subnetLong &= $mask; // Masque le sous-réseau

            return ($ipLong & $mask) === $subnetLong;
        }

        // Pour IPv6 (simplifié, ne gère pas tous les cas)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && 
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip === $subnet; // Simplification 
        }

        return false;
    }
}
