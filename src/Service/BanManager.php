<?php

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use DateTimeImmutable;
use DateInterval;

class BanManager
{
    private SessionInterface $session;
    private string $key = 'geolocator_bans';

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @throws \Exception
     */
    public function addBan(string $ip, string $reason, string $duration): void
    {
        $bans = $this->session->get($this->key, []);
        $expire = (new DateTimeImmutable())->add(new DateInterval('PT'.(new \DateInterval('PT'.$this->convertDuration($duration)))->s.'S'));
        $bans[$ip] = ['reason' => $reason, 'expires_at' => $expire->format('c')];
        $this->session->set($this->key, $bans);
    }

    /**
     * @throws \Exception
     */
    public function isBanned(string $ip): bool
    {
        $bans = $this->session->get($this->key, []);
        if (!isset($bans[$ip])) {
            return false;
        }
        $expires = new DateTimeImmutable($bans[$ip]['expires_at']);
        if ($expires < new DateTimeImmutable()) {
            unset($bans[$ip]);
            $this->session->set($this->key, $bans);
            return false;
        }
        return true;
    }

    public function listBans(): array
    {
        return $this->session->get($this->key, []);
    }

    public function removeBan(string $ip): void
    {
        $bans = $this->session->get($this->key, []);
        unset($bans[$ip]);
        $this->session->set($this->key, $bans);
    }

    private function convertDuration(string $duration): string
    {
        // Simple parser: supports hours only
        if (preg_match('/^(\d+)\s*hours?$/', $duration, $m)) {
            $hours = $m[1];
            return ($hours * 3600);
        }
        return '3600';
    }
}
