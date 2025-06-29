<?php

use GeolocatorBundle\Filter\FilterChain;
use GeolocatorBundle\Filter\FilterInterface;
use GeolocatorBundle\Filter\FilterResult;
use Symfony\Component\HttpFoundation\Request;

beforeEach(function () {
    $this->request = new Request();
    $this->geoData = ['country' => 'FR'];
});

it('returns null if no filter blocks', function () {
    $chain = new FilterChain([]);
    expect($chain->process($this->request, $this->geoData))->toBeNull();
});

it('returns FilterResult when a filter blocks', function () {
    $filter = new class implements FilterInterface {
        public function apply(Request $r, array $d): bool { return true; }
    };
    $chain = new FilterChain([$filter]);
    $res = $chain->process($this->request, $this->geoData);
    expect($res)->toBeInstanceOf(FilterResult::class);
    expect($res->isBlocked())->toBeTrue();
    expect($res->getCountry())->toBe('FR');
});

it('picks the first blocking filter', function () {
    $f1 = new class implements FilterInterface {
        public function apply(Request $r, array $d): bool { return false; }
    };
    $f2 = new class implements FilterInterface {
        public function apply(Request $r, array $d): bool { return true; }
    };
    $chain = new FilterChain([$f1, $f2]);
    $res = $chain->process($this->request, $this->geoData);
    expect($res->getReason())->toContain(get_class($f2));
});
