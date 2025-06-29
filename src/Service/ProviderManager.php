<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Manages the rotation (round-robin) and availability
 * of IP geolocation providers.
 */
final class ProviderManager
{
    /** @var GeolocationProviderInterface[] */
    private array $providers;
    private int   $currentIndex   = 0;
    private int   $maxRetries;
    private LoggerInterface $logger;
    private array $triedProviders = [];

    /**
     * @param iterable<GeolocationProviderInterface> $providers  Providers configurés.
     * @param LoggerInterface                        $logger     Logger PSR-3.
     * @param int                                    $maxRetries Nombre max. de tentatives (≥ 1).
     *
     * @throws InvalidArgumentException Si la configuration est invalide.
     */
    public function __construct(
        iterable $providers,
        LoggerInterface $logger,
        int $maxRetries = 3
    ) {
        $this->providers = iterator_to_array($providers);
        if (empty($this->providers)) {
            throw new InvalidArgumentException('Aucun provider de géolocalisation n’est configuré.');
        }
        foreach ($this->providers as $p) {
            if (!$p instanceof GeolocationProviderInterface) {
                throw new InvalidArgumentException(sprintf('Provider must implement GeolocationProviderInterface, %s given.', is_object($p) ? get_class($p) : gettype($p)
                ));
            }
        }
        if ($maxRetries < 1) {
            throw new InvalidArgumentException('maxRetries doit être ≥ 1.');
        }

        $this->logger     = $logger;
        $this->maxRetries = $maxRetries;
    }

/**
     * Returns a provider available according to the Round-Robin strategy,
     * ignoring those already tried.
     *
     * @RETurn geolocationProviderinterface
     *
     */
    public function getNextProvider(): GeolocationProviderInterface
    {
        if (count($this->triedProviders) >= count($this->providers)) {
            $this->logger->critical('All providers have already been tried.');
            throw new \RuntimeException('No geolocation provider available.');
        }

        $start = $this->currentIndex;
        while (true) {
            $provider = $this->providers[$this->currentIndex % count($this->providers)];
            $this->currentIndex = ($this->currentIndex + 1) % count($this->providers);

            $name = $provider->getName();
            if (in_array($name, $this->triedProviders, true)) {
                if ($this->currentIndex === $start) {
                    break;
                }
                continue;
            }

            if ($provider->isAvailable()) {
                return $provider;
            }

            $this->logger->info('Provider {provider} unavailable, moving to next.', ['provider' => $name]
            );
            $this->triedProviders[] = $name;

            if ($this->currentIndex === $start) {
                break;
            }
        }

        $this->logger->critical('No provider available after complete rotation.');
        throw new \RuntimeException('No geolocation provider available.');
    }

    /**
     * Mark a provider as having already been tried.
     */
    public function markProviderTried(GeolocationProviderInterface $provider): void
    {
        $name = $provider->getName();
        if (!in_array($name, $this->triedProviders, true)) {
            $this->triedProviders[] = $name;
        }
    }

    /**
     * Reset the statement of the tests and the rotation index.
     */
    public function resetTriedProviders(): void
    {
        $this->triedProviders = [];
        $this->currentIndex   = 0;
    }

    /**
     * Recovers a provider by name.
     *
     * @param string $name
     * @return GeolocationProviderInterface
     *
     * @throws InvalidArgumentException If not found.
     */
    public function getProviderByName(string $name): GeolocationProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                return $provider;
            }
        }
        throw new InvalidArgumentException(sprintf('Provider "%s" not configured.', $name));
    }

    /**
     * Adjust the maximum number of attempts.
     *
     * @throws InvalidArgumentException Si < 1.
     */
    public function setMaxRetries(int $maxRetries): void
    {
        if ($maxRetries < 1) {
            throw new InvalidArgumentException('maxRetries must be ≥ 1.');
        }
        $this->maxRetries = $maxRetries;
    }
}
