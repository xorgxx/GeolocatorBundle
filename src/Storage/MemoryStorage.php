<?php

namespace GeolocatorBundle\Storage;

class MemoryStorage implements StorageInterface
{
    /**
     * @var array<string, array{ip: string, reason: string, expiration: string|null, timestamp: int}>
     */
    private array $bans = [];

    /**
     * @var array<string, int>
     */
    private array $attempts = [];

    /**
     * {@inheritdoc}
     */
    public function isBanned(string $ip): bool
    {
        if (!isset($this->bans[$ip])) {
            return false;
        }

        $ban = $this->bans[$ip];

        // Vérifier si le ban est expiré
        if ($ban['expiration'] !== null) {
            $expirationDate = new \DateTime($ban['expiration']);
            if ($expirationDate < new \DateTime()) {
                $this->removeBan($ip);
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function addBan(string $ip, string $reason, ?\DateTimeInterface $expiration = null): void
    {
        $this->bans[$ip] = [
            'ip' => $ip,
            'reason' => $reason,
            'expiration' => $expiration ? $expiration->format(\DateTimeInterface::ATOM) : null,
            'timestamp' => time(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function removeBan(string $ip): void
    {
        if (isset($this->bans[$ip])) {
            unset($this->bans[$ip]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBanInfo(string $ip): ?array
    {
        return $this->bans[$ip] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllBans(): array
    {
        return $this->bans;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanExpiredBans(): int
    {
        $count = 0;
        $now = new \DateTime();

        foreach ($this->bans as $ip => $ban) {
            if ($ban['expiration'] !== null) {
                $expirationDate = new \DateTime($ban['expiration']);
                if ($expirationDate < $now) {
                    $this->removeBan($ip);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementAttempt(string $ip): int
    {
        if (!isset($this->attempts[$ip])) {
            $this->attempts[$ip] = 0;
        }

        return ++$this->attempts[$ip];
    }

    /**
     * {@inheritdoc}
     */
    public function getAttemptCount(string $ip): int
    {
        return $this->attempts[$ip] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resetAttempts(string $ip): void
    {
        if (isset($this->attempts[$ip])) {
            unset($this->attempts[$ip]);
        }
    }
}
