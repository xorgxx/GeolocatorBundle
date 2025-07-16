<?php

namespace GeolocatorBundle\Service;

use Psr\Log\LoggerInterface;
use GeolocatorBundle\Provider\GeolocationProviderInterface;

class ProviderManager
{
    private string $defaultProvider;
    /** @var string[] */
    private array $fallback;
    /** @var GeolocationProviderInterface[] */
    private array $providers = [];
    private ?LoggerInterface $logger;

    public function __construct(
        array $config,
        iterable $taggedProviders,
        LoggerInterface $logger = null
    ) {
        $this->defaultProvider = $config['providers']['default'] ?? 'ipapi';
        $this->fallback = $config['providers']['fallback'] ?? [];
        $this->logger = $logger;

        // Enregistrer les providers taggés
        foreach ($taggedProviders as $provider) {
            if (!$provider instanceof GeolocationProviderInterface) {
                continue;
            }
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function getDefault(): ?GeolocationProviderInterface
    {
        return $this->providers[$this->defaultProvider] ?? null;
    }

    /**
     * @return GeolocationProviderInterface[]
     */
    public function getFallbackChain(): array
    {
        $chain = [];
        foreach ($this->fallback as $alias) {
            if (isset($this->providers[$alias])) {
                $chain[] = $this->providers[$alias];
            }
        }
        return $chain;
    }

    private function log(string $message): void
    {
        $this->logger?->warning($message);
    }
}
