<?php

namespace GeolocatorBundle\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected HttpClientInterface $httpClient;
    protected array $config;

    public function __construct(HttpClientInterface $httpClient, array $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsVpnDetection(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isVpn(string $ip): bool
    {
        if (!$this->supportsVpnDetection()) {
            throw new \RuntimeException(sprintf('Le provider %s ne supporte pas la détection de VPN', $this->getName()));
        }

        $geoLocation = $this->getGeoLocation($ip);
        return $geoLocation->isVpn() === true || $geoLocation->isProxy() === true || $geoLocation->isTor() === true;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getName(): string;

    /**
     * Remplace les placeholders dans l'URL DSN.
     */
    protected function formatDsn(string $dsn, string $ip): string
    {
        $replacements = [
            '{ip}' => $ip,
            '{apikey}' => $this->config['apikey'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $dsn);
    }
}
