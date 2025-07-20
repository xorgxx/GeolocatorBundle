<?php

namespace GeolocatorBundle\Provider;

use GeolocatorBundle\Model\GeoLocation;

class FindipProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    public function getGeoLocation(string $ip): GeoLocation
    {
        $url = $this->formatDsn($this->config['dsn'], $ip);

        try {
            $response = $this->httpClient->request('GET', $url);
            $data = $response->toArray();

            // Vérifier si la réponse indique une erreur
            if (isset($data['success']) && $data['success'] === false) {
                throw new \RuntimeException($data['message'] ?? 'Erreur inconnue de l\'API ipqualityscore');
            }

            // Normaliser les données de VPN/Proxy
            $data['is_vpn'] = $data['vpn'] ?? false;
            $data['is_proxy'] = $data['proxy'] ?? false;
            $data['is_tor'] = $data['tor'] ?? false;

            return new GeoLocation($ip, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible d\'obtenir les données de géolocalisation depuis ipqualityscore: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ipqualityscore';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsVpnDetection(): bool
    {
        return true; // ipqualityscore offre des capacités de détection VPN/Proxy/Tor
    }
}
