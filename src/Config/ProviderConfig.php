<?php

namespace GeolocatorBundle\Config;

class ProviderConfig
{
    private bool $enabled = true;
    private string $dsn;
    private ?string $apikey = null;
    private int $timeout = 5;
    private int $retryAttempts = 2;
    private bool $fallback = false;

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function getApikey(): ?string
    {
        return $this->apikey;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function isFallback(): bool
    {
        return $this->fallback;
    }
}
