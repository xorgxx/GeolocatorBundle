<?php

use GeolocatorBundle\Service\BotDetector;
use InvalidArgumentException;

it('detects bot by user-agent fragment', function () {
    $detector = new BotDetector(['Googlebot', 'Bingbot'], true);
    expect($detector->isBot('Mozilla/5.0 Googlebot/2.1'))->toBeTrue();
    expect($detector->isBot('Mozilla/5.0 Chrome/90.0'))->toBeFalse();
});

it('should challenge only when enabled and bot', function () {
    $d1 = new BotDetector(['bot'], false);
    expect($d1->shouldChallenge('mybot'))->toBeFalse();

    $d2 = new BotDetector(['bot'], true);
    expect($d2->shouldChallenge('mybot'))->toBeTrue();
});

it('throws on empty pattern', function () {
    new BotDetector([''], true);
})->throws(InvalidArgumentException::class);
