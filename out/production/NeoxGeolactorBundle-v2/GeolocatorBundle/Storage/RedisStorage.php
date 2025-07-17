<?php

namespace GeolocatorBundle\Storage;

use Predis\Client;

class RedisStorage implements StorageInterface
{
    private Client $redis;
    private string $banPrefix = 'geolocator:ban:';
    private string $attemptPrefix = 'geolocator:attempt:';

    public function __construct(string $redisDsn)
    {
        try {
            $this->redis = new Client($redisDsn);
            // Vérifier la connexion
            $this->redis->ping();
        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible de se connecter au serveur Redis: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isBanned(string $ip): bool
    {
        $banKey = $this->banPrefix . $ip;
        $ban = $this->redis->get($banKey);

        if (!$ban) {
            return false;
        }

        $banData = json_decode($ban, true);

        // Vérifier si le ban est expiré
        if ($banData['expiration'] !== null) {
            $expirationDate = new \DateTime($banData['expiration']);
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
        $banKey = $this->banPrefix . $ip;

        $banData = [
            'ip' => $ip,
            'reason' => $reason,
            'expiration' => $expiration ? $expiration->format(\DateTimeInterface::ATOM) : null,
            'timestamp' => time(),
        ];

        if ($expiration !== null) {
            // Calculer la durée en secondes jusqu'à l'expiration
            $ttl = $expiration->getTimestamp() - time();
            $this->redis->setex($banKey, $ttl, json_encode($banData));
        } else {
            // Ban permanent
            $this->redis->set($banKey, json_encode($banData));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeBan(string $ip): void
    {
        $banKey = $this->banPrefix . $ip;
        $this->redis->del($banKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getBanInfo(string $ip): ?array
    {
        $banKey = $this->banPrefix . $ip;
        $ban = $this->redis->get($banKey);

        if (!$ban) {
            return null;
        }

        return json_decode($ban, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllBans(): array
    {
        $bans = [];
        $cursor = '0';
        $pattern = $this->banPrefix . '*';

        do {
            // Utiliser SCAN au lieu de KEYS pour éviter de bloquer Redis
            [$cursor, $keys] = $this->redis->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);

            if (!empty($keys)) {
                // Utiliser MGET pour récupérer plusieurs valeurs en une seule commande
                $values = $this->redis->mget($keys);

                foreach ($keys as $i => $key) {
                    if ($values[$i]) {
                        try {
                            $banData = json_decode($values[$i], true, 512, JSON_THROW_ON_ERROR);
                            $ip = str_replace($this->banPrefix, '', $key);
                            $bans[$ip] = $banData;
                        } catch (\JsonException $e) {
                            // Ignorer les entrées JSON invalides
                        }
                    }
                }
            }
        } while ($cursor !== '0');

        return $bans;
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function cleanExpiredBans(): int
    {
        // Redis gère automatiquement l'expiration des clés avec SETEX,
        // mais cette méthode nettoie les bans permanents qui sont expirés manuellement

        $count = 0;
        $cursor = '0';
        $pattern = $this->banPrefix . '*';
        $now = new \DateTime();
        $keysToDelete = [];

        try {
            do {
                // Utiliser SCAN pour itérer sur les clés sans bloquer Redis
                [$cursor, $keys] = $this->redis->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);

                if (!empty($keys)) {
                    $values = $this->redis->mget($keys);

                    foreach ($keys as $i => $key) {
                        if ($values[$i]) {
                            try {
                                $banData = json_decode($values[$i], true, 512, JSON_THROW_ON_ERROR);
                                if ($banData['expiration'] !== null) {
                                    $expirationDate = new \DateTime($banData['expiration']);
                                    if ($expirationDate < $now) {
                                        $keysToDelete[] = $key;
                                    }
                                }
                            } catch (\JsonException $e) {
                                // Ignorer les entrées JSON invalides
                            }
                        }
                    }
                }

                // Supprimer les clés par lots pour optimiser les performances
                if (count($keysToDelete) >= 20) {
                    if (!empty($keysToDelete)) {
                        $count += $this->redis->del($keysToDelete);
                        $keysToDelete = [];
                    }
                }

            } while ($cursor !== '0');

            // Supprimer les clés restantes
            if (!empty($keysToDelete)) {
                $count += $this->redis->del($keysToDelete);
            }

            return $count;
        } catch (\Exception $e) {
            // En cas d'erreur, journaliser et continuer
            error_log('Erreur lors du nettoyage des bans expirés : ' . $e->getMessage());
            return $count;
        }
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function incrementAttempt(string $ip, int $ttl = 3600): int
    {
        $attemptKey = $this->attemptPrefix . $ip;
        $count = $this->redis->incr($attemptKey);

        // Définir une expiration si c'est la première tentative
        if ($count === 1) {
            $this->redis->expire($attemptKey, $ttl);
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttemptCount(string $ip): int
    {
        $attemptKey = $this->attemptPrefix . $ip;
        $count = $this->redis->get($attemptKey);

        return $count ? (int) $count : 0;
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function resetAttempts(string $ip): void
    {
        $attemptKey = $this->attemptPrefix . $ip;
        $this->redis->del($attemptKey);
    }

    /**
     * Vérifie si le serveur Redis est disponible.
     *
     * @return bool Retourne true si Redis est disponible, false sinon
     */
    public function isAvailable(): bool
    {
        try {
            return $this->redis->ping() === 'PONG';
        } catch (\Exception $e) {
            return false;
        }
    }
}
