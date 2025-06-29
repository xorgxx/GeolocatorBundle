<?php

use GeolocatorBundle\Service\AsyncGeolocator;
use GeolocatorBundle\Service\GeolocationCache;
use GeolocatorBundle\Message\GeolocateMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

beforeEach(function () {
    $this->messageBus = $this->createMock(MessageBusInterface::class);
    $this->cache      = $this->createMock(GeolocationCache::class);
    $this->logger     = $this->createMock(LoggerInterface::class);
});

it('returns cached data when present', function () {
    $ip   = '1.2.3.4';
    $data = ['ip' => $ip];
    $this->cache->method('has')->with($ip)->willReturn(true);
    $this->cache->method('get')->with($ip)->willReturn($data);

    $svc = new AsyncGeolocator($this->messageBus, $this->cache, $this->logger, false);
    expect($svc->geolocate($ip))->toBe($data);
});

it('does not dispatch when async disabled and no cache', function () {
    $ip = '5.6.7.8';
    $this->cache->method('has')->with($ip)->willReturn(false);
    $this->messageBus->expects($this->never())->method('dispatch');

    $svc = new AsyncGeolocator($this->messageBus, $this->cache, $this->logger, false);
    expect($svc->geolocate($ip))->toBeNull();
});

it('dispatches a message when async enabled and no cache', function () {
    $ip = '9.9.9.9';
    $this->cache->method('has')->with($ip)->willReturn(false);
    $this->messageBus
        ->expects($this->once())
        ->method('dispatch')
        ->with($this->callback(fn($msg) => $msg instanceof GeolocateMessage && $msg->getIp() === $ip));

    $svc = new AsyncGeolocator($this->messageBus, $this->cache, $this->logger, true);
    expect($svc->geolocate($ip))->toBeNull();
});

it('logs error if dispatch throws', function () {
    $ip = '8.8.4.4';
    $this->cache->method('has')->with($ip)->willReturn(false);
    $this->messageBus
        ->method('dispatch')
        ->willThrowException(new \RuntimeException('broker down'));
    $this->logger
        ->expects($this->once())
        ->method('error')
        ->with('Échec du dispatch du message de géolocalisation', $this->arrayHasKey('exception'));

    $svc = new AsyncGeolocator($this->messageBus, $this->cache, $this->logger, true);
    $svc->geolocate($ip);
});
