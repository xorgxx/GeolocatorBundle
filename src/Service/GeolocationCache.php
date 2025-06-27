<?php

namespace GeolocatorBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Cache\InvalidArgumentException;

class GeolocationCache
{
    private CacheItemPoolInterface $cachePool;
    private ProviderManager        $providerManager;
    private int                    $ttl;

    public function __construct(CacheItemPoolInterface $cachePool, ProviderManager $providerManager, int $ttl = 300)
    {
        $this->cachePool = $cachePool;
        $this->providerManager = $providerManager;
        $this->ttl = $ttl;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function locate(string $ip): array
    {
        $cacheKey = $this->getCacheKeyForIp($ip);
        $cacheItem = $this->cachePool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $geolocationProvider = $this->providerManager->getNextProvider();
        $geoData = $geolocationProvider->locate($ip);

        $this->saveToCache($cacheItem, $geoData);

        return $geoData;
    }

    private function getCacheKeyForIp(string $ip): string
    {
        return 'geo_' . md5($ip);
    }

    private function saveToCache($cacheItem, array $geoData): void
    {
        $cacheItem->set($geoData);
        $cacheItem->expiresAfter($this->ttl);
        $this->cachePool->save($cacheItem);
    }
}
