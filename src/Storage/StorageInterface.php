<?php

namespace GeolocatorBundle\Storage;

interface StorageInterface
{
    /**
     * Vérifie si une IP est bannie.
     */
    public function isBanned(string $ip): bool;

    /**
     * Ajoute une IP à la liste des IPs bannies.
     * @param string $ip L'adresse IP à bannir
     * @param string $reason La raison du ban
     * @param \DateTimeInterface|null $expiration Date d'expiration du ban (null = permanent)
     */
    public function addBan(string $ip, string $reason, ?\DateTimeInterface $expiration = null): void;

    /**
     * Supprime une IP de la liste des IPs bannies.
     */
    public function removeBan(string $ip): void;

    /**
     * Récupère les informations sur le ban d'une IP.
     * @return array{ip: string, reason: string, expiration: string|null, timestamp: int}|null
     */
    public function getBanInfo(string $ip): ?array;

    /**
     * Récupère toutes les IPs bannies.
     * @return array<string, array{ip: string, reason: string, expiration: string|null, timestamp: int}>
     */
    public function getAllBans(): array;

    /**
     * Nettoie les bans expirés.
     * @return int Nombre de bans supprimés
     */
    public function cleanExpiredBans(): int;

    /**
     * Incrémente le compteur de tentatives pour une IP.
     * @return int Le nouveau nombre de tentatives
     */
    public function incrementAttempt(string $ip): int;

    /**
     * Récupère le nombre de tentatives pour une IP.
     */
    public function getAttemptCount(string $ip): int;

    /**
     * Réinitialise le compteur de tentatives pour une IP.
     */
    public function resetAttempts(string $ip): void;
}
