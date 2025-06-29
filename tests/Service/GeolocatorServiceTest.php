<?php

use GeolocatorBundle\Service\GeolocatorService;
use GeolocatorBundle\Service\GeolocationCache;
use GeolocatorBundle\Service\ProviderManager;
use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use RuntimeException;

beforeEach(function () {
    $this->cache           = $this->createStub(GeolocationCache::class);
    $this->providerManager = $this->createStub(ProviderManager::class);
    $this->logger          = new NullLogger();
    $this->svc             = new GeolocatorService($this->providerManager, $this->cache, $this->logger);
});

it('throws on invalid IP', function () {
    $this->svc->locateIp('bad-ip');
})->throws(InvalidArgumentException::class);

it('returns cached result if present', function () {
    $this->cache->method('get')->willReturn(['ip'=>'1.1.1.1']);
    expect($this->svc->locateIp('1.1.1.1'))->toBe(['ip'=>'1.1.1.1']);
});

it('returns provider result on success', function () {
    $this->cache->method('get')->willReturn(null);
    $provider = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'p'; }
        public function locate(string $ip): array { return ['ip'=>$ip]; }
        public function isAvailable(): bool { return true; }
    };
    $this->providerManager->method('resetTriedProviders');
    $this->providerManager->method('getNextProvider')->willReturn($provider);

    expect($this->svc->locateIp('2.2.2.2'))->toBe(['ip'=>'2.2.2.2']);
});

it('returns fallback on failure when enabled', function () {
    $this->cache->method('get')->willReturn(null);
    $this->providerManager->method('resetTriedProviders');
    $this->providerManager->method('getNextProvider')
                          ->willThrowException(new RuntimeException('fail'));

    $res = $this->svc->locateIp('3.3.3.3');
    expect($res)->toBeArray()->and($res['fallback'])->toBeTrue();
});

it('throws when fallback disabled', function () {
    $svc = new GeolocatorService($this->providerManager, $this->cache, $this->logger, 1, false);
    $this->cache->method('get')->willReturn(null);
    $this->providerManager->method('resetTriedProviders');
    $this->providerManager->method('getNextProvider')
                          ->willThrowException(new RuntimeException('fail'));

    $svc->locateIp('4.4.4.4');
})->throws(RuntimeException::class);
