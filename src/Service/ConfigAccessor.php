<?php

namespace GeolocatorBundle\Service;

class ConfigAccessor
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $value = $this->config;

        foreach ($parts as $part) {
            if (!is_array($value) || !isset($value[$part])) {
                return $default;
            }

            $value = $value[$part];
        }

        return $value;
    }

    public function getProviderConfig(string $provider): array
    {
        return $this->get("providers.list.{$provider}", []);
    }

    public function getDefaultProvider(): string
    {
        return $this->get('providers.default', 'ipapi');
    }

    public function getFallbackProviders(): array
    {
        return $this->get('providers.fallback', []);
    }
}
