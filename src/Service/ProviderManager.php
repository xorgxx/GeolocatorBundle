<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Gère la rotation (round-robin) et la disponibilité
 * des fournisseurs de géolocalisation IP.
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
     * @throws \InvalidArgumentException Si la configuration est invalide.
     */
    public function __construct(
        iterable $providers,
        LoggerInterface $logger,
        int $maxRetries = 3
    ) {
        $this->providers = iterator_to_array($providers);
        if (empty($this->providers)) {
            throw new \InvalidArgumentException('Aucun provider de géolocalisation n’est configuré.');
        }
        foreach ($this->providers as $p) {
            if (!$p instanceof GeolocationProviderInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Le provider doit implémenter GeolocationProviderInterface, %s fourni.',
                    is_object($p) ? get_class($p) : gettype($p)
                ));
            }
        }
        if ($maxRetries < 1) {
            throw new \InvalidArgumentException('maxRetries doit être ≥ 1.');
        }

        $this->logger     = $logger;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Renvoie un provider disponible selon la stratégie round-robin,
     * en ignorant ceux déjà essayés.
     *
     * @return GeolocationProviderInterface
     *
     * @throws \RuntimeException Si tous les providers ont échoué ou sont indisponibles.
     */
    public function getNextProvider(): GeolocationProviderInterface
    {
        if (count($this->triedProviders) >= count($this->providers)) {
            $this->logger->critical('Tous les providers ont déjà été essayés.');
            throw new \RuntimeException('Aucun provider de géolocalisation disponible.');
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

            $this->logger->info(
                'Provider {provider} indisponible, passage au suivant.',
                ['provider' => $name]
            );
            $this->triedProviders[] = $name;

            if ($this->currentIndex === $start) {
                break;
            }
        }

        $this->logger->critical('Aucun provider disponible après rotation complète.');
        throw new \RuntimeException('Aucun provider de géolocalisation disponible.');
    }

    /**
     * Marque un provider comme ayant déjà été essayé.
     */
    public function markProviderTried(GeolocationProviderInterface $provider): void
    {
        $name = $provider->getName();
        if (!in_array($name, $this->triedProviders, true)) {
            $this->triedProviders[] = $name;
        }
    }

    /**
     * Réinitialise l’état des essais et l’index de rotation.
     */
    public function resetTriedProviders(): void
    {
        $this->triedProviders = [];
        $this->currentIndex   = 0;
    }

    /**
     * Récupère un provider par son nom.
     *
     * @param string $name
     * @return GeolocationProviderInterface
     *
     * @throws \InvalidArgumentException Si non trouvé.
     */
    public function getProviderByName(string $name): GeolocationProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getName() === $name) {
                return $provider;
            }
        }
        throw new \InvalidArgumentException(sprintf('Provider « %s » non configuré.', $name));
    }

    /**
     * Ajuste le nombre maximal de tentatives.
     *
     * @throws \InvalidArgumentException Si < 1.
     */
    public function setMaxRetries(int $maxRetries): void
    {
        if ($maxRetries < 1) {
            throw new \InvalidArgumentException('maxRetries doit être ≥ 1.');
        }
        $this->maxRetries = $maxRetries;
    }
}
