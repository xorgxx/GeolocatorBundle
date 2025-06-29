<?php

use GeolocatorBundle\EventSubscriber\GeoFilterSubscriber;
use GeolocatorBundle\Service\IpResolver;
use GeolocatorBundle\Service\GeolocatorService;
use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Filter\FilterChain;
use GeolocatorBundle\Event\GeoFilterBlockedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

beforeEach(function () {
    $this->ipResolver       = $this->createStub(IpResolver::class);
    $this->geolocatorService= $this->createStub(GeolocatorService::class);
    $this->banManager       = $this->createStub(BanManager::class);
    $this->filterChain      = $this->createStub(FilterChain::class);
    $this->dispatcher       = $this->createMock(EventDispatcherInterface::class);
    $this->params           = $this->createStub(ParameterBagInterface::class);
    $this->params
        ->method('get')
        ->with('geolocator')
        ->willReturn(['ban_duration'=>'1 hour','blocked_countries'=>['FR']]);

    $this->subscriber = new GeoFilterSubscriber(
        $this->ipResolver,
        $this->geolocatorService,
        $this->banManager,
        $this->filterChain,
        $this->dispatcher,
        $this->params
    );

    $kernel = $this->createMock(HttpKernelInterface::class);
    $this->request = new Request();
    $this->event   = new RequestEvent($kernel, $this->request, HttpKernelInterface::MAIN_REQUEST);
});

it('ignores sub-requests', function () {
    $subEvent = new RequestEvent(
        $this->createMock(HttpKernelInterface::class),
        $this->request,
        HttpKernelInterface::SUB_REQUEST
    );
    $this->subscriber->onKernelRequest($subEvent);
    expect($subEvent->hasResponse())->toBeFalse();
});

it('blocks when IP already banned', function () {
    $this->ipResolver->method('resolve')->willReturn('1.1.1.1');
    $this->banManager->method('isBanned')->willReturn(true);

    $this->dispatcher
        ->expects($this->once())
        ->method('dispatch')
        ->with($this->callback(fn($e) => $e instanceof GeoFilterBlockedEvent));

    $this->subscriber->onKernelRequest($this->event);
    expect($this->event->getResponse())->toBeInstanceOf(Response::class)
                                       ->and($this->event->getResponse()->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);
});

it('blocks by filterChain', function () {
    $this->ipResolver->method('resolve')->willReturn('1.1.1.1');
    $this->banManager->method('isBanned')->willReturn(false);
    $res = new \GeolocatorBundle\Filter\FilterResult(true,'r','US');
    $this->filterChain->method('process')->willReturn($res);

    $this->subscriber->onKernelRequest($this->event);
    expect($this->event->getResponse()->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);
});

it('blocks by country config', function () {
    $this->ipResolver->method('resolve')->willReturn('1.1.1.1');
    $this->banManager->method('isBanned')->willReturn(false);
    $this->filterChain->method('process')->willReturn(null);
    $this->geolocatorService
        ->method('locateIp')
        ->willReturn(['country'=>'FR']);

    $this->subscriber->onKernelRequest($this->event);
    expect($this->event->getResponse()->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);
});
