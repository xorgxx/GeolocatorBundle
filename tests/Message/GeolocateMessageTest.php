<?php

use GeolocatorBundle\Message\GeolocateMessage;
use InvalidArgumentException;

it('generates a uuid requestId when none given', function () {
    $msg = new GeolocateMessage('1.1.1.1');
    expect($msg->getRequestId())->not->toBeEmpty();
    expect(filter_var($msg->getRequestId(), FILTER_VALIDATE_REGEXP, [
        'options' => ['regexp' => '/^[0-9a-fA-F\-]{36}$/']
    ]))->not->toBeFalse();
});

it('allows passing a custom requestId', function () {
    $custom = 'my-id-123';
    $msg = new GeolocateMessage('1.1.1.1', null, $custom);
    expect($msg->getRequestId())->toBe($custom);
});

it('throws on invalid IP', function () {
    new GeolocateMessage('not-an-ip');
})->throws(InvalidArgumentException::class);
