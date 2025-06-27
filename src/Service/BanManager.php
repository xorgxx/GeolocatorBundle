<?php

namespace GeolocatorBundle\Service;

use Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use DateTimeImmutable;
use DateInterval;

class BanManager
{
    private SessionInterface $session;
    private const BANS_SESSION_KEY = 'geolocator_bans';
    private const DEFAULT_DURATION_SECONDS = 3600;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @throws Exception
     */
    public function addBan(string $ip, string $reason, string $duration): void
    {
        $bans = $this->session->get(self::BANS_SESSION_KEY, []);
        $durationSeconds = $this->parseDurationToSeconds($duration);
        $expireAt = (new DateTimeImmutable())->add(new DateInterval('PT' . $durationSeconds . 'S'));
        $bans[ $ip ] = [
            'reason'     => $reason,
            'expires_at' => $expireAt->format('c'),
        ];
        $this->session->set(self::BANS_SESSION_KEY, $bans);
    }

    /**
     * @throws Exception
     */
    public function isBanned(string $ip): bool
    {
        $bans = $this->session->get(self::BANS_SESSION_KEY, []);
        if (!isset($bans[ $ip ])) {
            return false;
        }
        $expiresAt = new DateTimeImmutable($bans[ $ip ][ 'expires_at' ]);
        if ($expiresAt < new DateTimeImmutable()) {
            unset($bans[ $ip ]);
            $this->session->set(self::BANS_SESSION_KEY, $bans);
            return false;
        }
        return true;
    }

    public function listBans(): array
    {
        return $this->session->get(self::BANS_SESSION_KEY, []);
    }

    public function removeBan(string $ip): void
    {
        $bans = $this->session->get(self::BANS_SESSION_KEY, []);
        unset($bans[ $ip ]);
        $this->session->set(self::BANS_SESSION_KEY, $bans);
    }

    /**
     * Convertit la durée textuelle en secondes.
     */
    private function parseDurationToSeconds(string $duration): int
    {
        if (preg_match('/^(\d+)\s*hours?$/i', $duration, $matches)) {
            return (int)$matches[ 1 ] * 3600;
        }
        return self::DEFAULT_DURATION_SECONDS;
    }
}
