<?php

use GeolocatorBundle\Service\GeolocationCache;
use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;

beforeEach(function () {
    // Stub provider
    $provider = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'stub'; }
        public function locate(string $ip): array { return ['ip'=>$ip]; }
        public function isAvailable(): bool { return true; }
    };
    // Simple manager
    $this->providerManager = new class($provider) {
        private $p;
        public function __construct($p){$this->p=$p;}
        public function getNextProvider(): GeolocationProviderInterface { return $this->p; }
    };
    $this->cachePool = new ArrayAdapter();
    $this->cacheSvc  = new GeolocationCache($this->cachePool, $this->providerManager, 3600);
});

it('caches and returns provider result', function () {
    $res1 = $this->cacheSvc->locate('1.2.3.4');
    expect($res1)->toBe(['ip'=>'1.2.3.4']);

    // Second call: from cache
    $res2 = $this->cacheSvc->locate('1.2.3.4');
    expect($res2)->toBe($res1);
});

it('throws on invalid IP', function () {
    $this->cacheSvc->locate('not-ip');
})->throws(InvalidArgumentException::class);
