<?php

use GeolocatorBundle\Service\IpResolver;
use Symfony\Component\HttpFoundation\Request;

beforeEach(function () {
    $this->resolver = new IpResolver();
});

it('uses REMOTE_ADDR when no XFF', function () {
    $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '5.5.5.5']);
    expect($this->resolver->resolve($request))->toBe('5.5.5.5');
});

it('extracts first public IP from X-Forwarded-For', function () {
    $request = new Request();
    $request->headers->set('X-Forwarded-For', '10.0.0.1, 8.8.8.8');
    expect($this->resolver->resolve($request))->toBe('8.8.8.8');
});

it('returns null if no valid IP', function () {
    $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => 'invalid']);
    $request->headers->set('X-Forwarded-For', '192.168.0.1');
    expect($this->resolver->resolve($request))->toBeNull();
});
