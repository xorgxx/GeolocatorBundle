<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service principal pour la géolocalisation d’adresses IP.
 * Gère la mise en cache, les tentatives auprès des providers et le fallback.
 */
final class GeolocatorService
{
    private ProviderManager    $providerManager;
    private GeolocationCache   $geolocCache;
    private LoggerInterface    $logger;
    private int                $maxRetries;
    private bool               $fallbackEnabled;

    /**
     * @param ProviderManager  $providerManager  Gestionnaire de providers round-robin.
     * @param GeolocationCache $geolocCache      Cache des résultats géolocalisés.
     * @param LoggerInterface  $logger           Logger PSR-3 (Monolog ou NullLogger).
     * @param int              $maxRetries       Nombre max. de tentatives (>=1).
     * @param bool             $fallbackEnabled  Active le retour par défaut en cas d’échec.
     *
     * @throws InvalidArgumentException Si $maxRetries < 1.
     */
    public function __construct(
        ProviderManager $providerManager,
        GeolocationCache $geolocCache,
        LoggerInterface $logger,
        int $maxRetries = 3,
        bool $fallbackEnabled = true
    ) {
        if ($maxRetries < 1) {
            throw new InvalidArgumentException(sprintf(
                'maxRetries doit être >= 1, %d fourni',
                $maxRetries
            ));
        }

        $this->providerManager = $providerManager;
        $this->geolocCache     = $geolocCache;
        $this->logger          = $logger;
        $this->maxRetries      = $maxRetries;
        $this->fallbackEnabled = $fallbackEnabled;
    }

    /**
     * Géolocalise une IP avec gestion d’erreurs et fallback.
     *
     * @param string      $ip                Adresse IP à géolocaliser.
     * @param string|null $preferredProvider Nom du provider à forcer (optionnel).
     *
     * @return array Données de géolocalisation.
     *
     * @throws InvalidArgumentException Si l’IP est invalide.
     * @throws RuntimeException         Si le fallback est désactivé et toutes les tentatives échouent.
     */
    public function locateIp(string $ip, ?string $preferredProvider = null): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException(sprintf('Adresse IP invalide : %s', $ip));
        }

        // 1) Cache hit ?
        $cacheResult = $this->geolocCache->get($ip);
        if (null !== $cacheResult) {
            $this->logger->debug(sprintf('IP %s trouvée en cache', $ip));
            return $cacheResult;
        }

        // 2) Reset providers pour cette requête
        $this->providerManager->resetTriedProviders();

        $lastError = null;
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $provider = null !== $preferredProvider
                    ? $this->providerManager->getProviderByName($preferredProvider)
                    : $this->providerManager->getNextProvider();

                $this->logger->info(sprintf(
                    'Tentative [%d/%d] géolocalisation pour %s avec %s',
                    $attempt, $this->maxRetries, $ip, $provider->getName()
                ));

                $result = $provider->locate($ip);

                if (($result['error'] ?? false) === true) {
                    $this->logger->warning(sprintf(
                        'Provider %s a renvoyé une erreur pour %s : %s',
                        $provider->getName(),
                        $ip,
                        $result['message'] ?? 'inconnue'
                    ));
                    $this->providerManager->markProviderTried($provider);
                    $lastError = $result;
                    continue;
                }

                // Succès !
                $this->logger->info(sprintf(
                    'Géolocalisation réussie pour %s avec %s',
                    $ip, $provider->getName()
                ));
                $this->geolocCache->set($ip, $result);
                return $result;
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    'Exception [%d/%d] pour %s : %s',
                    $attempt, $this->maxRetries, $ip, $e->getMessage()
                ), ['exception' => $e]);
                $lastError = [
                    'error'   => true,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // 3) Toutes les tentatives ont échoué
        $this->logger->critical(sprintf(
            'Échec après %d tentatives pour %s',
            $this->maxRetries, $ip
        ), ['lastError' => $lastError]);

        if (!$this->fallbackEnabled) {
            throw new RuntimeException(sprintf(
                'Géolocalisation impossible pour %s',
                $ip
            ));
        }

        // 4) Retour fiche fallback
        return [
            'error'      => true,
            'ip'         => $ip,
            'message'    => sprintf(
                'Géolocalisation échouée après %d tentatives',
                $this->maxRetries
            ),
            'last_error' => $lastError,
            'fallback'   => true,
            'country'    => null,
            'continent'  => null,
            'is_vpn'     => false,
            'asn'        => null,
            'isp'        => null,
        ];
    }

    /**
     * Active ou désactive le fallback.
     */
    public function setFallbackEnabled(bool $enabled): void
    {
        $this->fallbackEnabled = $enabled;
    }

    /**
     * Définit le nombre maximal de tentatives providers.
     *
     * @throws InvalidArgumentException Si $maxRetries < 1.
     */
    public function setMaxRetries(int $maxRetries): void
    {
        if ($maxRetries < 1) {
            throw new InvalidArgumentException(sprintf(
                'maxRetries doit être >= 1, %d fourni',
                $maxRetries
            ));
        }
        $this->maxRetries = $maxRetries;
    }
}
