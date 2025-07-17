<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Storage\StorageInterface;
use Psr\Log\LoggerInterface;

class BanManager
{
    private StorageInterface $storage;
    private array $config;
    private LoggerInterface $logger;

    public function __construct(StorageInterface $storage, array $config, LoggerInterface $logger)
    {
        $this->storage = $storage;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Vérifie si une IP est bannie.
     */
    public function isBanned(string $ip): bool
    {
        return $this->storage->isBanned($ip);
    }

    /**
     * Banni une IP avec une raison spécifique.
     * @param bool $permanent Si true, le ban sera permanent
     * @return \DateTimeInterface|null Date d'expiration ou null si permanent
     */
    public function banIp(string $ip, string $reason, bool $permanent = false): ?\DateTimeInterface
    {
        $expiration = null;

        if (!$permanent) {
            $duration = $this->config['ban_duration'] ?? '1 hour';
            $expiration = new \DateTime();
            $expiration->modify('+' . $duration);
        }

        $this->storage->addBan($ip, $reason, $expiration);
        $this->storage->resetAttempts($ip);

        $banType = $permanent ? 'permanent' : 'temporaire';
        $expirationStr = $expiration ? $expiration->format('Y-m-d H:i:s') : 'jamais';
        $this->logger->warning("IP {$ip} bannie ({$banType}) jusqu'à {$expirationStr} pour raison: {$reason}");

        return $expiration;
    }

    /**
     * Banni une IP de façon permanente.
     */
    public function banIpPermanently(string $ip, string $reason): void
    {
        $this->banIp($ip, $reason, true);
    }

    /**
     * Supprime le ban d'une IP.
     */
    public function unbanIp(string $ip): void
    {
        if ($this->isBanned($ip)) {
            $this->storage->removeBan($ip);
            $this->logger->info("IP {$ip} débannie manuellement");
        }
    }

    /**
     * Enregistre une tentative pour une IP et la banni si le seuil est dépassé.
     * @return bool True si l'IP a été bannie suite à cette tentative
     */
    public function recordAttempt(string $ip, string $type = 'generic'): bool
    {
        if ($this->isBanned($ip)) {
            return true;
        }

        $attempts = $this->storage->incrementAttempt($ip);
        $maxAttempts = $this->config['max_attempts'] ?? 10;

        if ($attempts >= $maxAttempts) {
            $this->banIp($ip, "Trop de tentatives ({$attempts}) de type '{$type}'");
            return true;
        }

        return false;
    }

    /**
     * Récupère les informations sur le ban d'une IP.
     */
    public function getBanInfo(string $ip): ?array
    {
        return $this->storage->getBanInfo($ip);
    }

    /**
     * Récupère tous les bans actifs.
     */
    public function getAllBans(): array
    {
        return $this->storage->getAllBans();
    }

    /**
     * Nettoie les bans expirés.
     */
    public function cleanExpiredBans(): int
    {
        $count = $this->storage->cleanExpiredBans();
        if ($count > 0) {
            $this->logger->info("{$count} bans expirés ont été supprimés");
        }
        return $count;
    }
}
