<?php

namespace GeolocatorBundle\Storage;

class JsonStorage implements StorageInterface
{
    private string $filePath;
    private array $data = [
        'bans' => [],
        'attempts' => [],
    ];
    private bool $loaded = false;

    public function __construct( $filePath)
    {
        $this->filePath = $filePath["file"] ?? $filePath;
    }

    /**
     * Charge les données depuis le fichier JSON.
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (file_exists($this->filePath)) {
            $content = file_get_contents($this->filePath);
            if ($content) {
                $data = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->data = $data;
                }
            }
        }

        $this->loaded = true;
    }

    /**
     * Sauvegarde les données dans le fichier JSON.
     */
    private function save(): void
    {
        $directory = dirname($this->filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->filePath, json_encode($this->data, JSON_PRETTY_PRINT));
    }

    /**
     * {@inheritdoc}
     */
    public function isBanned(string $ip): bool
    {
        $this->load();

        if (!isset($this->data['bans'][$ip])) {
            return false;
        }

        $ban = $this->data['bans'][$ip];

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
        $this->load();

        $this->data['bans'][$ip] = [
            'ip' => $ip,
            'reason' => $reason,
            'expiration' => $expiration ? $expiration->format(\DateTimeInterface::ATOM) : null,
            'timestamp' => time(),
        ];

        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function removeBan(string $ip): void
    {
        $this->load();

        if (isset($this->data['bans'][$ip])) {
            unset($this->data['bans'][$ip]);
            $this->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBanInfo(string $ip): ?array
    {
        $this->load();
        return $this->data['bans'][$ip] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllBans(): array
    {
        $this->load();
        return $this->data['bans'];
    }

    /**
     * {@inheritdoc}
     */
    public function cleanExpiredBans(): int
    {
        $this->load();

        $count = 0;
        $now = new \DateTime();
        $modified = false;

        foreach ($this->data['bans'] as $ip => $ban) {
            if ($ban['expiration'] !== null) {
                $expirationDate = new \DateTime($ban['expiration']);
                if ($expirationDate < $now) {
                    unset($this->data['bans'][$ip]);
                    $count++;
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $this->save();
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementAttempt(string $ip): int
    {
        $this->load();

        if (!isset($this->data['attempts'][$ip])) {
            $this->data['attempts'][$ip] = 0;
        }

        $this->data['attempts'][$ip]++;
        $this->save();

        return $this->data['attempts'][$ip];
    }

    /**
     * {@inheritdoc}
     */
    public function getAttemptCount(string $ip): int
    {
        $this->load();
        return $this->data['attempts'][$ip] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resetAttempts(string $ip): void
    {
        $this->load();

        if (isset($this->data['attempts'][$ip])) {
            unset($this->data['attempts'][$ip]);
            $this->save();
        }
    }
}
