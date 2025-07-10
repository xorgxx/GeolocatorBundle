<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use DateTimeImmutable;
use DateInterval;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Management of IP banns in session with automatic expiration.
 */
final class BanManager
{
    private SessionInterface $session;
    private const BANS_SESSION_KEY = 'geolocator_bans';
    private const DEFAULT_DURATION_SECONDS = 3600;

    /**
     * @param SessionInterface $session Storage of banns in HTTP session.
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Adds a ban for a given IP address.
     *
     * @param string $ip IP address to ban.
     * @param string $reason Ban reason.
     * @param string $duration Ban duration (ex. "1 hour", "30 minutes", "P1DT2H").
     *
     * @throws InvalidArgumentException If IP or duration is invalid.
     */
    public function addBan(string $ip, string $reason, string $duration): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException(sprintf('Invalid IP address: %s', $ip));
        }

        $durationSeconds = $this->parseDurationToSeconds($duration);
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->add(new DateInterval(sprintf('PT%dS', $durationSeconds)));

        $bans = $this->getBans();
        $bans[$ip] = [
            'reason'     => $reason,
            'expires_at' => $expiresAt->format(DateTimeImmutable::ATOM),
        ];
        $this->saveBans($bans);
    }

    /**
     * Check if an IP is banned.
     *
     * @Param String $ip IP address to check.
     * @RETURN BOOL TRUE If the ban is active, false otherwise.
     * @throws \Exception
     */
    public function isBanned(string $ip): bool
    {
        $bans = $this->getBans();

        if (!isset($bans[$ip])) {
            return false;
        }

        $expiresAt = new DateTimeImmutable($bans[$ip]['expires_at'], new DateTimeZone('UTC'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if ($expiresAt < $now) {
            unset($bans[$ip]);
            $this->saveBans($bans);
            return false;
        }

        return true;
    }

    /**
     * List all banns.
     * @return array<string, array{reason: string, expires_at: string}>
     */
    public function listBans(): array
    {
        return $this->getBans();
    }

    /**
     * Remove the ban from an IP.
     *
     * @Param String $IP IP address to Dépanner.
     */
    public function removeBan(string $ip): void
    {
        $bans = $this->getBans();
        if (isset($bans[$ip])) {
            unset($bans[$ip]);
            $this->saveBans($bans);
        }
    }

    /**
     * Converts a duration string to seconds.
     *
     * Supports:
     *  - "10 seconds", "5 minutes", "2 hours", "1 day"
     *  - ISO 8601 durations (ex. "P1DT2H30M")
     *
     * @param string $duration
     * @return int
     * @throws InvalidArgumentException If format is invalid.
     */
    private function parseDurationToSeconds(string $duration): int
    {
        $duration = trim($duration);
        $patterns = [
            '/^(?P<value>\d+)\s*second(?:s)?$/i' => 1,
            '/^(?P<value>\d+)\s*minute(?:s)?$/i' => 60,
            '/^(?P<value>\d+)\s*hour(?:s)?$/i'   => 3600,
            '/^(?P<value>\d+)\s*day(?:s)?$/i'    => 86400,
        ];

        foreach ($patterns as $pattern => $multiplier) {
            if (preg_match($pattern, $duration, $matches)) {
                return (int)$matches['value'] * $multiplier;
            }
        }

        // ISO 8601 (P[n]DT[n]H[n]M[n]S)
        if (preg_match(
            '/^P(?:(?P<days>\d+)D)?T?(?:(?P<hours>\d+)H)?(?:(?P<minutes>\d+)M)?(?:(?P<seconds>\d+)S)?$/',
            $duration,
            $matches
        )) {
            $seconds = 0;
            if (!empty($matches['days'])) {
                $seconds += (int)$matches['days'] * 86400;
            }
            if (!empty($matches['hours'])) {
                $seconds += (int)$matches['hours'] * 3600;
            }
            if (!empty($matches['minutes'])) {
                $seconds += (int)$matches['minutes'] * 60;
            }
            if (!empty($matches['seconds'])) {
                $seconds += (int)$matches['seconds'];
            }
            return $seconds > 0 ? $seconds : self::DEFAULT_DURATION_SECONDS;
        }

        throw new InvalidArgumentException(sprintf('Invalid duration format: "%s".', $duration));
    }

    private function getBans(): array
    {
        return $this->session->get(self::BANS_SESSION_KEY, []);
    }

    private function saveBans(array $bans): void
    {
        $this->session->set(self::BANS_SESSION_KEY, $bans);
    }
}
