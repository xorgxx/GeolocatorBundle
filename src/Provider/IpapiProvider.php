<?php

namespace GeolocatorBundle\Provider;

use GeolocatorBundle\Model\GeoLocation;

class IpapiProvider extends AbstractProvider
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
            if (isset($data['error']) && $data['error'] === true) {
                throw new \RuntimeException($data['reason'] ?? 'Erreur inconnue de l\'API ipapi');
            }

            return new GeoLocation($ip, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible d\'obtenir les données de géolocalisation depuis ipapi: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ipapi';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsVpnDetection(): bool
    {
        return false; // ipapi n'offre pas de détection VPN dans sa version gratuite
    }
}
