<?php

use GeolocatorBundle\Service\ProviderManager;
use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\NullLogger;
use InvalidArgumentException;
use RuntimeException;

beforeEach(function () {
    // Rien de spécifique
});

it('throws if no providers', function () {
    new ProviderManager([], new NullLogger(), 3);
})->throws(InvalidArgumentException::class);

it('throws if element invalid', function () {
    new ProviderManager([new stdClass()], new NullLogger(), 3);
})->throws(InvalidArgumentException::class);

it('throws if maxRetries<1', function () {
    $stub = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'x'; }
        public function locate(string $ip): array { return []; }
        public function isAvailable(): bool { return true; }
    };
    new ProviderManager([$stub], new NullLogger(), 0);
})->throws(InvalidArgumentException::class);

it('returns first available in round-robin', function () {
    $a = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'A'; }
        public function locate(string $ip): array { return []; }
        public function isAvailable(): bool { return false; }
    };
    $b = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'B'; }
        public function locate(string $ip): array { return []; }
        public function isAvailable(): bool { return true; }
    };
    $mgr = new ProviderManager([$a, $b], new NullLogger(), 3);
    expect($mgr->getNextProvider())->toBe($b);
});

it('cycles providers', function () {
    $p1 = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'P1'; }
        public function locate(string $ip): array { return []; }
        public function isAvailable(): bool { return true; }
    };
    $p2 = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'P2'; }
        public function locate(string $ip): array { return []; }
        public function isAvailable(): bool { return true; }
    };
    $mgr = new ProviderManager([$p1, $p2], new NullLogger(), 3);
    expect($mgr->getNextProvider())->toBe($p1);
    expect($mgr->getNextProvider())->toBe($p2);
    expect($mgr->getNextProvider())->toBe($p1);
});

it('fails when all tried', function () {
    $p = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'X'; }
        public function locate(string $ip): array { return []; }
        public function isAvailable(): bool { return false; }
    };
    $mgr = new ProviderManager([$p], new NullLogger(), 3);
    expect(fn() => $mgr->getNextProvider())->toThrow(RuntimeException::class);
});

it('getProviderByName works or throws', function () {
    $p1 = new class implements GeolocationProviderInterface {
        public function getName(): string { return 'foo'; }
        public function locate(string $ip): array { return []; }
        public function isAvailable(): bool { return true; }
    };
    $mgr = new ProviderManager([$p1], new NullLogger(), 3);
    expect($mgr->getProviderByName('foo'))->toBe($p1);
    expect(fn() => $mgr->getProviderByName('bar'))->toThrow(InvalidArgumentException::class);
});
