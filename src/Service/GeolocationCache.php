<?php

namespace GeolocatorBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use GeolocatorBundle\Provider\GeolocationProviderInterface;

class GeolocationCache
{
    private CacheItemPoolInterface $cachePool;
    private ProviderManager $providerManager;
    private int $ttl;

    public function __construct(CacheItemPoolInterface $cachePool, ProviderManager $providerManager, int $ttl = 300)
    {
        $this->cachePool = $cachePool;
        $this->providerManager = $providerManager;
        $this->ttl = $ttl;
    }

    public function locate(string $ip): array
    {
        $cacheKey = 'geo_' . md5($ip);
        $item = $this->cachePool->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        $provider = $this->providerManager->getNextProvider();
        $data = $provider->locate($ip);

        $item->set($data);
        $item->expiresAfter($this->ttl);
        $this->cachePool->save($item);

        return $data;
    }
}
