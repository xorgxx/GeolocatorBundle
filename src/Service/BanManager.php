<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use DateTimeImmutable;
use DateInterval;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Gestion des bans d’IP en session avec expiration automatique.
 */
final class BanManager
{
    private SessionInterface $session;
    private const BANS_SESSION_KEY = 'geolocator_bans';
    private const DEFAULT_DURATION_SECONDS = 3600;

    /**
     * @param SessionInterface $session Stockage des bans en session HTTP.
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Ajoute un ban pour une IP donnée.
     *
     * @param string $ip        Adresse IP à bannir.
     * @param string $reason    Raison du ban.
     * @param string $duration  Durée du ban (ex. "1 hour", "30 minutes", "P1DT2H").
     *
     * @throws InvalidArgumentException Si l'IP ou la durée est invalide.
     */
    public function addBan(string $ip, string $reason, string $duration): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException(sprintf('IP invalide : %s', $ip));
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
     * Vérifie si une IP est bannie.
     *
     * @param string $ip Adresse IP à vérifier.
     * @return bool      True si la ban est active, false sinon.
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
     * Liste tous les bans.
     *
     * @return array<string, array{reason: string, expires_at: string}>
     */
    public function listBans(): array
    {
        return $this->getBans();
    }

    /**
     * Retire le ban d'une IP.
     *
     * @param string $ip Adresse IP à débannir.
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
     * Convertit une durée sous forme de chaîne en secondes.
     *
     * Supporte :
     *  - "10 seconds", "5 minutes", "2 hours", "1 day"
     *  - ISO 8601 durations (ex. "P1DT2H30M")
     *
     * @param string $duration
     * @return int
     * @throws InvalidArgumentException Si le format est invalide.
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

        throw new InvalidArgumentException(sprintf('Format de durée invalide : "%s".', $duration));
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
