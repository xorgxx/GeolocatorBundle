<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use GeolocatorBundle\Service\ProviderManager;

/**
 * PSR-6 cache for IP geolocation data.
 */
final class GeolocationCache
{
    private CacheItemPoolInterface $cachePool;
    private ProviderManager         $providerManager;
    private int                    $ttl;

    /**
     * @param CacheItemPoolInterface $cachePool PSR-6 cache pool.
     * @param ProviderManager $providerManager Round-robin provider manager.
     * @param int $ttl Time to live in seconds (>= 0).
     *
     * @throws \InvalidArgumentException If TTL is negative.
     */
    public function __construct(
        CacheItemPoolInterface $cachePool,
        ProviderManager $providerManager,
        int $ttl = 300
    ) {
        if ($ttl < 0) {
            throw new \InvalidArgumentException(sprintf('Invalid TTL: %d', $ttl));
        }

        $this->cachePool       = $cachePool;
        $this->providerManager = $providerManager;
        $this->ttl             = $ttl;
    }

    /**
     * Géolocalise une IP en utilisant le cache PSR-6 :
     * - renvoie la valeur en cache si disponible,
     * - sinon interroge le provider suivant, stocke puis renvoie le résultat.
     *
     * @param string $ip Adresse IP à géolocaliser.
     * @return array     Données de géolocalisation.
     *
     * @throws CacheInvalidArgumentException
     * @throws \InvalidArgumentException             Si l’IP est invalide.
     */
    public function locate(string $ip): array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException(sprintf('Invalid IP: %s', $ip));
        }

        $cacheKey  = $this->getCacheKeyForIp($ip);
        $cacheItem = $this->cachePool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $provider = $this->providerManager->getNextProvider();
        $geoData  = $provider->locate($ip);

        $this->saveToCache($cacheItem, $geoData);

        return $geoData;
    }

    /**
     * Construit la clé de cache à partir de l’IP.
     */
    private function getCacheKeyForIp(string $ip): string
    {
        return 'geo_' . md5($ip);
    }

    /**
     * Enregistre les données en cache avec expiration.
     *
     * @param CacheItemInterface $cacheItem Item PSR-6 à remplir.
     * @param array              $geoData   Données de géolocalisation.
     */
    private function saveToCache(CacheItemInterface $cacheItem, array $geoData): void
    {
        $cacheItem->set($geoData);
        $cacheItem->expiresAfter($this->ttl);
        $this->cachePool->save($cacheItem);
    }
}
