<?php

use GeolocatorBundle\Service\WebhookNotifier;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->httpClient = $this->createMock(HttpClientInterface::class);
    $this->logger     = $this->createMock(LoggerInterface::class);
});

it('throws on invalid URL', function () {
    new WebhookNotifier($this->httpClient, ['not-a-url'], $this->logger);
})->throws(\InvalidArgumentException::class);

it('logs info on 2xx response', function () {
    $urls = ['https://ex.com/h1', 'https://ex.com/h2'];
    $resp = $this->createMock(ResponseInterface::class);
    $resp->method('getStatusCode')->willReturn(200);

    $this->httpClient
        ->expects($this->exactly(2))
        ->method('request')
        ->willReturn($resp);

    $this->logger
        ->expects($this->exactly(2))
        ->method('info');

    (new WebhookNotifier($this->httpClient, $urls, $this->logger))
        ->notify('{"a":1}');
});

it('logs error on ≥400', function () {
    $resp = $this->createMock(ResponseInterface::class);
    $resp->method('getStatusCode')->willReturn(500);
    $resp->method('getContent')->with(false)->willReturn('err');

    $this->httpClient->method('request')->willReturn($resp);

    $this->logger
        ->expects($this->once())
        ->method('error');

    (new WebhookNotifier($this->httpClient, ['https://ex/h'], $this->logger))
        ->notify('{}');
});

it('catches transport exceptions', function () {
    $this->httpClient
        ->method('request')
        ->willThrowException($this->createMock(TransportExceptionInterface::class));

    $this->logger
        ->expects($this->once())
        ->method('error');

    (new WebhookNotifier($this->httpClient, ['https://ex/h'], $this->logger))
        ->notify('{}');
});
