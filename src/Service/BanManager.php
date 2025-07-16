<?php
namespace GeolocatorBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use GeolocatorBundle\Bridge\GeolocatorEventBridgeInterface;

class BanManager
{
    private array $bans = [];
    private int $maxAttempts;
    private int $ttl;
    private array $permanentCountries;
    private string $storageType;
    private ?string $storageFile;
    private ?string $redisDsn;
    private ?LoggerInterface $logger;
    private bool $simulate;
    private ?GeolocatorEventBridgeInterface $eventBridge;

    public function __construct(
        array $config,
        LoggerInterface $logger = null,
        ?GeolocatorEventBridgeInterface $eventBridge = null
    ) {
        $this->maxAttempts = $config['bans']['max_attempts'] ?? 10;
        $this->ttl = $config['bans']['ttl'] ?? 3600;
        $this->permanentCountries = $config['bans']['permanent_countries'] ?? [];
        $this->storageType = $config['storage']['type'] ?? 'json';
        $this->storageFile = $config['storage']['file'] ?? null;
        $this->redisDsn = $config['storage']['redis_dsn'] ?? null;
        $this->logger = $logger;
        $this->simulate = $config['simulate'] ?? false;
        $this->eventBridge = $eventBridge;
        $this->loadBans();
    }

    public function isBanned(string $ip, ?SessionInterface $session = null, ?array $geo = null): bool
    {
        if ($session !== null && $session->has('geolocator_banned')) {
            return true;
        }
        if (isset($this->bans[$ip]) && ($this->bans[$ip]['until'] === null || $this->bans[$ip]['until'] > time())) {
            return true;
        }
        if ($geo && !empty($this->permanentCountries) && in_array($geo['country'] ?? '', $this->permanentCountries, true)) {
            return true;
        }
        return false;
    }

    public function banIp(string $ip, ?string $reason = null, ?array $geo = null, ?SessionInterface $session = null): void
    {
        if ($this->simulate) {
            $this->log("[SIMULATE] Would ban IP $ip " . ($reason ? "($reason)" : ''));
            return;
        }

        $until = $this->ttl > 0 ? (time() + $this->ttl) : null;
        $this->bans[$ip] = [
            'until' => $until,
            'reason' => $reason,
            'geo' => $geo,
            'at' => time(),
        ];

        $this->saveBans();

        if ($session !== null) {
            $session->set('geolocator_banned', true);
        }

        $payload = [
            'ip' => $ip,
            'reason' => $reason,
            'geo' => $geo,
            'timestamp' => time(),
        ];

        if ($this->eventBridge instanceof GeolocatorEventBridgeInterface) {
            $this->eventBridge->notify($payload, 'ban');
        }

        $this->log("Ban IP $ip" . ($reason ? " ($reason)" : ''));
    }

    public function unbanIp(string $ip): void
    {
        unset($this->bans[$ip]);
        $this->saveBans();
        $this->log("Unban IP $ip");
    }

    public function getBans(): array
    {
        return $this->bans;
    }
    
    /**
     * Alias for getBans() to maintain compatibility with tests
     */
    public function listBans(): array
    {
        return $this->getBans();
    }
    
    /**
     * Alias for banIp() to maintain compatibility with GeoFilterSubscriber
     */
    public function addBan(string $ip, ?string $reason = null, ?string $duration = null): void
    {
        // Convert duration string to TTL if provided
        $originalTtl = $this->ttl;
        if ($duration !== null) {
            $this->ttl = $this->parseDuration($duration);
        }
        
        $this->banIp($ip, $reason);
        
        // Restore original TTL
        if ($duration !== null) {
            $this->ttl = $originalTtl;
        }
    }
    
    /**
     * Alias for unbanIp() to maintain compatibility with tests
     */
    public function removeBan(string $ip): void
    {
        $this->unbanIp($ip);
    }
    
    /**
     * Parse duration string like "1 hour", "30 minutes" to seconds
     */
    private function parseDuration(string $duration): int
    {
        $parts = explode(' ', trim(strtolower($duration)));
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid duration format: $duration");
        }
        
        $value = (int)$parts[0];
        $unit = rtrim($parts[1], 's'); // Remove trailing 's' if present
        
        return match($unit) {
            'second' => $value,
            'minute' => $value * 60,
            'hour' => $value * 3600,
            'day' => $value * 86400,
            'week' => $value * 604800,
            'month' => $value * 2592000,
            default => throw new \InvalidArgumentException("Unknown time unit: $unit")
        };
    }

    public function checkAttempts(string $ip, int $attempts): void
    {
        if ($attempts >= $this->maxAttempts) {
            $this->banIp($ip, 'Too many attempts');
        }
    }

    private function log(string $msg): void
    {
        if ($this->logger) {
            $this->logger->warning($msg);
        }
    }

    private function loadBans(): void
    {
        if ($this->storageType === 'json' && $this->storageFile && file_exists($this->storageFile)) {
            $json = file_get_contents($this->storageFile);
            $this->bans = json_decode($json, true) ?: [];
        } elseif ($this->storageType === 'memory') {
            $this->bans = [];
        } elseif ($this->storageType === 'redis' && $this->redisDsn) {
            $this->loadBansFromRedis();
        }
    }

    private function saveBans(): void
    {
        if ($this->storageType === 'json' && $this->storageFile) {
            file_put_contents($this->storageFile, json_encode($this->bans));
        } elseif ($this->storageType === 'redis' && $this->redisDsn) {
            $this->saveBansToRedis();
        }
    }
    
    private function loadBansFromRedis(): void
    {
        try {
            $redis = new \Redis();
            if ($redis->connect(parse_url($this->redisDsn, PHP_URL_HOST), parse_url($this->redisDsn, PHP_URL_PORT))) {
                $path = parse_url($this->redisDsn, PHP_URL_PATH);
                if ($path && strlen($path) > 1) {
                    $redis->select(intval(substr($path, 1)));
                }
                
                $keys = $redis->keys('geolocator_ban:*');
                $this->bans = [];
                
                foreach ($keys as $key) {
                    $ip = str_replace('geolocator_ban:', '', $key);
                    $banData = json_decode($redis->get($key), true);
                    if ($banData) {
                        $this->bans[$ip] = $banData;
                    }
                }
                
                $redis->close();
                $this->log("Loaded " . count($this->bans) . " bans from Redis");
            }
        } catch (\Exception $e) {
            $this->log("Error loading bans from Redis: " . $e->getMessage());
        }
    }
    
    private function saveBansToRedis(): void
    {
        try {
            $redis = new \Redis();
            if ($redis->connect(parse_url($this->redisDsn, PHP_URL_HOST), parse_url($this->redisDsn, PHP_URL_PORT))) {
                $path = parse_url($this->redisDsn, PHP_URL_PATH);
                if ($path && strlen($path) > 1) {
                    $redis->select(intval(substr($path, 1)));
                }
                
                // Clear existing bans
                $keys = $redis->keys('geolocator_ban:*');
                if (!empty($keys)) {
                    $redis->del($keys);
                }
                
                // Save current bans
                foreach ($this->bans as $ip => $banData) {
                    $key = 'geolocator_ban:' . $ip;
                    $ttl = null;
                    
                    if (isset($banData['until']) && $banData['until'] !== null) {
                        $ttl = $banData['until'] - time();
                        if ($ttl <= 0) {
                            // Skip expired bans
                            continue;
                        }
                    }
                    
                    $redis->set($key, json_encode($banData));
                    
                    if ($ttl !== null) {
                        $redis->expire($key, $ttl);
                    }
                }
                
                $redis->close();
                $this->log("Saved " . count($this->bans) . " bans to Redis");
            }
        } catch (\Exception $e) {
            $this->log("Error saving bans to Redis: " . $e->getMessage());
        }
    }
}
