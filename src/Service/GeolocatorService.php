<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GeolocatorService
{
    private ProviderManager $providerManager;
    private GeolocationCache $geolocCache;
    private LoggerInterface $logger;
    private int $maxRetries;
    private bool $fallbackEnabled;

    public function __construct(
        ProviderManager $providerManager,
        GeolocationCache $geolocCache,
        ?LoggerInterface $logger = null,
        int $maxRetries = 3,
        bool $fallbackEnabled = true
    ) {
        $this->providerManager = $providerManager;
        $this->geolocCache = $geolocCache;
        $this->logger = $logger ?? new NullLogger();
        $this->maxRetries = $maxRetries;
        $this->fallbackEnabled = $fallbackEnabled;
    }

    /**
     * Géolocalise une IP avec gestion d'erreurs et fallback
     *
     * @param string $ip L'adresse IP à géolocaliser
     * @param string|null $preferredProvider Fournisseur préféré (optionnel)
     * @return array Données de géolocalisation
     */
    public function locateIp(string $ip, ?string $preferredProvider = null): array
    {
        // Vérifier d'abord dans le cache
        $cacheResult = $this->geolocCache->get($ip);
        if ($cacheResult !== null) {
            return $cacheResult;
        }

        // Réinitialiser le gestionnaire de fournisseurs pour cette nouvelle requête
        $this->providerManager->resetTriedProviders();

        $retryCount = 0;
        $lastError = null;

        while ($retryCount < $this->maxRetries) {
            try {
                // Obtenir le prochain fournisseur
                $provider = $this->providerManager->getNextProvider();

                $this->logger->info('Tentative de géolocalisation de {ip} avec le fournisseur {provider}', [
                    'ip' => $ip,
                    'provider' => $provider->getName(),
                    'retry' => $retryCount
                ]);

                $result = $provider->locate($ip);

                // Vérifier si le résultat contient une erreur
                if (isset($result['error']) && $result['error'] === true) {
                    $this->logger->warning('Erreur lors de la géolocalisation avec {provider}: {message}', [
                        'provider' => $provider->getName(),
                        'message' => $result['message'] ?? 'erreur inconnue',
                        'ip' => $ip
                    ]);

                    // Marquer ce fournisseur comme essayé pour éviter de le réutiliser
                    $this->providerManager->markProviderTried($provider);
                    $lastError = $result;
                    $retryCount++;
                    continue;
                }

                // Si on arrive ici, c'est que la géolocalisation a réussi
                $this->logger->info('Géolocalisation réussie pour {ip} avec {provider}', [
                    'ip' => $ip,
                    'provider' => $provider->getName()
                ]);

                // Stocker en cache
                $this->geolocCache->set($ip, $result);

                return $result;

            } catch (\Exception $e) {
                $this->logger->error('Exception lors de la géolocalisation: {message}', [
                    'message' => $e->getMessage(),
                    'ip' => $ip,
                    'retry' => $retryCount
                ]);

                $lastError = [
                    'error' => true,
                    'message' => $e->getMessage()
                ];

                $retryCount++;
            }
        }

        // Si on arrive ici, c'est que toutes les tentatives ont échoué
        $this->logger->critical('Échec de toutes les tentatives de géolocalisation pour {ip}', ['ip' => $ip]);

        // Retourner une réponse par défaut avec les informations d'erreur
        return [
            'error' => true,
            'ip' => $ip,
            'message' => 'Échec de la géolocalisation après ' . $this->maxRetries . ' tentatives',
            'last_error' => $lastError,
            'fallback' => true,
            'country' => null,
            'continent' => null,
            'is_vpn' => false, // Par défaut, on considère que ce n'est pas un VPN
            'asn' => null,
            'isp' => null
        ];
    }

    /**
     * Permet de désactiver ou réactiver le fallback
     */
    public function setFallbackEnabled(bool $enabled): void
    {
        $this->fallbackEnabled = $enabled;
    }

    /**
     * Permet de définir le nombre maximum de tentatives
     */
    public function setMaxRetries(int $maxRetries): void
    {
        $this->maxRetries = $maxRetries;
    }
}
