<?php

use GeolocatorBundle\Service\BanManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use InvalidArgumentException;

beforeEach(function () {
    $this->session    = new Session(new MockArraySessionStorage());
    $this->banManager = new BanManager($this->session);
});

it('starts with no bans', function () {
    expect($this->banManager->listBans())->toBeEmpty();
    expect($this->banManager->isBanned('1.2.3.4'))->toBeFalse();
});

it('adds and recognizes a ban', function () {
    $this->banManager->addBan('1.2.3.4', 'test', '1 hour');
    expect($this->banManager->isBanned('1.2.3.4'))->toBeTrue();
    expect($this->banManager->listBans())->toHaveKey('1.2.3.4');
});

it('removes a ban', function () {
    $this->banManager->addBan('1.2.3.4', 'test', '1 hour');
    $this->banManager->removeBan('1.2.3.4');
    expect($this->banManager->isBanned('1.2.3.4'))->toBeFalse();
});

it('throws on invalid IP', function () {
    $this->banManager->addBan('not-an-ip', 'r', '1 hour');
})->throws(InvalidArgumentException::class);

it('throws on invalid duration', function () {
    $this->banManager->addBan('1.2.3.4', 'r', 'invalid');
})->throws(InvalidArgumentException::class);
