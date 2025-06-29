<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use RuntimeException;

/**
 * Main service for the geolocation of IP addresses.
 * Manages the cache, attempts to the Providers and the Fallback.
 */
final class GeolocatorService
{
    private ProviderManager    $providerManager;
    private GeolocationCache   $geolocCache;
    private LoggerInterface    $logger;
    private int                $maxRetries;
    private bool               $fallbackEnabled;

    /**
     * @Param ProviderManager $ProviderManager Manager of Providers Round-Robin.
     * @Param geolocationcache $geoloccache hides geolocated results.
     * @Param Loggerinterface $Logger Logger PSR-3 (Monolog or Nulllogger).
     * @Param int $maxretries number max. attempts (> = 1).
     * @Param Bool $FallbacKeabled Activates the default in case of failure.
     *
     * @throws invalidargumentexception if $ maxretries <1.
     */
    public function __construct(
        ProviderManager $providerManager,
        GeolocationCache $geolocCache,
        LoggerInterface $logger,
        int $maxRetries = 3,
        bool $fallbackEnabled = true
    ) {
        if ($maxRetries < 1) {
            throw new InvalidArgumentException(sprintf('maxRetries must be >= 1, %d provided', $maxRetries
            ));
        }

        $this->providerManager = $providerManager;
        $this->geolocCache     = $geolocCache;
        $this->logger          = $logger;
        $this->maxRetries      = $maxRetries;
        $this->fallbackEnabled = $fallbackEnabled;
    }

    /**
     * Geolocalizes an IP with error management and Fallback.
     *
     * @Param String $IP IP address to geolocate.
     * @Param String | Null $preferredprovider name of the provider to force (optional).
     *
     * @return array geolocation data.
     *
     * @throws invalidargumentexception if the IP is invalid.
     * @throws runtimeexception if the fallback is disabled and all attempts fail.
     */
    public function locateIp(string $ip, ?string $preferredProvider = null): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException(sprintf('Invalid IP address: %s', $ip));
        }

        // 1) Cache hit?
        $cacheResult = $this->geolocCache->get($ip);
        if (null !== $cacheResult) {
            $this->logger->debug(sprintf('IP %s found in cache', $ip));
            return $cacheResult;
        }

        // 2) Reset providers for this request
        $this->providerManager->resetTriedProviders();

        $lastError = null;
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $provider = null !== $preferredProvider
                    ? $this->providerManager->getProviderByName($preferredProvider)
                    : $this->providerManager->getNextProvider();

                $this->logger->info(sprintf('Attempt [%d/%d] geolocation for %s with %s', $attempt, $this->maxRetries, $ip, $provider->getName()));

                $result = $provider->locate($ip);

                if (($result[ 'error' ] ?? false) === true) {
                    $this->logger->warning(sprintf('Provider %s returned an error for %s: %s', $provider->getName(), $ip, $result[ 'message' ] ?? 'unknown'));
                    $this->providerManager->markProviderTried($provider);
                    $lastError = $result;
                    continue;
                }

                // Success!
                $this->logger->info(sprintf('Geolocation successful for %s with %s', $ip, $provider->getName()));
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

        // 3) All attempts have failed
        $this->logger->critical(sprintf(
            'Failed after %d attempts for %s', $this->maxRetries, $ip), [ 'lastError' => $lastError ]);

        if (!$this->fallbackEnabled) {
            throw new RuntimeException(sprintf('Geolocation impossible for %s', $ip));
        }

        // 4) Return fallback data
        return [
            'error'      => true,
            'ip'         => $ip,
            'message'    => sprintf(
                'Geolocation failed after %d attempts', $this->maxRetries),
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
     * Activates or deactivates the Fallback.
     */
    public function setFallbackEnabled(bool $enabled): void
    {
        $this->fallbackEnabled = $enabled;
    }

    /**
     * Defines the maximum number of providers attempts.
     *
     * @throws invalidargumentexception if $max retries <1.
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
