<?php
namespace GeolocatorBundle\Service;

use Psr\Log\LoggerInterface;

class ProviderManager
{
    private string $defaultProvider;
    private array $providers;
    private array $fallback;
    private ?LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger = null)
    {
        $this->defaultProvider = $config['providers']['default'] ?? 'ipapi';
        $this->providers = $config['providers']['list'] ?? [];
        $this->fallback = $config['providers']['fallback'] ?? [];
        $this->logger = $logger;
    }

    /**
     * Retourne le DSN/URL du provider configuré (avec interpolation {ip}, {apikey}, etc.)
     */
    public function getProviderUrl(string $provider, string $ip, ?string $apikey = null): ?string
    {
        if (!isset($this->providers[$provider])) {
            $this->log("[ProviderManager] Provider '$provider' not configured");
            return null;
        }
        $dsn = $this->providers[$provider]['dsn'] ?? null;
        if (!$dsn) {
            $this->log("[ProviderManager] Provider '$provider' has no DSN");
            return null;
        }
        $url = str_replace(['{ip}', '{apikey}'], [$ip, $apikey ?? ($this->providers[$provider]['apikey'] ?? '')], $dsn);
        return $url;
    }

    /**
     * Donne la liste des providers à utiliser (main puis fallback)
     */
    public function getProviderChain(): array
    {
        $chain = [$this->defaultProvider];
        foreach ($this->fallback as $fb) {
            if (!in_array($fb, $chain, true)) {
                $chain[] = $fb;
            }
        }
        return $chain;
    }


    private function log(string $msg): void
    {
        if ($this->logger) {
            $this->logger->warning($msg);
        }
    }
}
