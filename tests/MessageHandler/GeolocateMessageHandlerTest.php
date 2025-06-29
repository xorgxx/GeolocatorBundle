<?php

use GeolocatorBundle\Message\GeolocateMessage;
use GeolocatorBundle\MessageHandler\GeolocateMessageHandler;
use GeolocatorBundle\Service\ProviderManager;
use GeolocatorBundle\Service\GeolocationCache;
use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    // Mocks des dépendances
    $this->providerManager = $this->createMock(ProviderManager::class);
    $this->cache           = $this->createMock(GeolocationCache::class);
    $this->logger          = $this->createMock(LoggerInterface::class);

    $this->handler = new GeolocateMessageHandler(
        $this->providerManager,
        $this->cache,
        $this->logger
    );
});

it('skips when cache has data', function () {
    $msg = new GeolocateMessage('2.2.2.2');
    $this->cache->method('has')->with('2.2.2.2')->willReturn(true);
    $this->logger->expects($this->once())->method('info')->with(
        'Données déjà en cache, skip',
        $this->arrayHasKey('request_id')
    );
    ($this->handler)($msg);
});

it('fetches, caches and logs on miss', function () {
    $msg = new GeolocateMessage('3.3.3.3', 'providerX', 'rid-1');

    $this->cache->method('has')->willReturn(false);
    $prov = $this->createMock(GeolocationProviderInterface::class);
    $prov->method('getName')->willReturn('providerX');
    $prov->method('locate')->with('3.3.3.3')->willReturn(['country'=>'US']);
    $this->providerManager
        ->method('getProviderByName')
        ->with('providerX')
        ->willReturn($prov);

    $this->cache->expects($this->once())->method('set')->with('3.3.3.3', ['country'=>'US']);
    $this->logger->expects($this->once())->method('info')->with(
        'Géolocalisation réussie',
        $this->arrayHasKey('provider')
    );

    ($this->handler)($msg);
});

it('falls back to nextProvider when no forceProvider', function () {
    $msg = new GeolocateMessage('4.4.4.4', null, 'rid-2');
    $this->cache->method('has')->willReturn(false);

    $prov = $this->createMock(GeolocationProviderInterface::class);
    $prov->method('getName')->willReturn('P');
    $prov->method('locate')->willReturn(['country'=>'FR']);
    $this->providerManager
        ->method('getNextProvider')
        ->willReturn($prov);

    $this->cache->expects('set')->with('4.4.4.4', ['country'=>'FR']);
    ($this->handler)($msg);
});

it('catches exceptions and logs error', function () {
    $msg = new GeolocateMessage('5.5.5.5', null, 'rid-3');
    $this->cache->method('has')->willReturn(false);
    $this->providerManager
        ->method('getNextProvider')
        ->willThrowException(new \RuntimeException('oops'));

    $this->logger
        ->expects($this->once())
        ->method('error')
        ->with('Erreur géoloc asynchrone', $this->arrayHasKey('error'));

    ($this->handler)($msg);
});
