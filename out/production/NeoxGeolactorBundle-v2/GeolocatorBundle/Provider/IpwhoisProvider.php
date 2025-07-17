<?php

namespace GeolocatorBundle\Provider;

use GeolocatorBundle\Model\GeoLocation;

class IpwhoisProvider extends AbstractProvider
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
                throw new \RuntimeException($data['message'] ?? 'Erreur inconnue de l\'API ipwhois');
            }

            return new GeoLocation($ip, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible d\'obtenir les données de géolocalisation depuis ipwhois: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ipwhois';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsVpnDetection(): bool
    {
        return false; // ipwhois n'offre pas de détection VPN dans sa version gratuite
    }
}
