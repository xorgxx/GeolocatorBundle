<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Provider\GeolocationProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProviderManager
{
    /** @var GeolocationProviderInterface[] */
    private array $providers;
    private int $currentIndex = 0;
    private const NO_PROVIDERS_CONFIGURED_MESSAGE = 'No geolocation providers configured.';
    private LoggerInterface $logger;
    private int $maxRetries;
    private array $triedProviders = [];

    public function __construct(
        iterable $geolocationProviders,
        ?LoggerInterface $logger = null,
        int $maxRetries = 3
    ) {
        $this->providers = iterator_to_array($geolocationProviders);
        $this->logger = $logger ?? new NullLogger();
        $this->maxRetries = $maxRetries;
    }

    /**
     * Retourne le prochain fournisseur disponible dans la liste
     */
    public function getNextProvider(): GeolocationProviderInterface
    {        
        if (empty($this->providers)) {
            throw new \RuntimeException(self::NO_PROVIDERS_CONFIGURED_MESSAGE);
        }

        // Si tous les fournisseurs ont été essayés sans succès
        if (count($this->triedProviders) >= count($this->providers)) {
            $this->logger->critical('Tous les fournisseurs de géolocalisation ont été essayés sans succès');
            throw new \RuntimeException('Tous les fournisseurs de géolocalisation sont indisponibles');
        }

        $startIndex = $this->currentIndex;
        $provider = null;

        // Cherche le prochain fournisseur disponible
        do {
            $candidat = $this->selectProviderByIndex($this->currentIndex);
            $this->currentIndex++;

            // Vérifie si le fournisseur est déjà dans la liste des essais
            $providerName = $candidat->getName();
            if (in_array($providerName, $this->triedProviders)) {
                continue;
            }

            // Vérifie si le fournisseur est disponible
            if ($candidat->isAvailable()) {
                $provider = $candidat;
                break;
            } else {
                $this->logger->info('Le fournisseur {provider} est marqué comme indisponible, passage au suivant', [
                    'provider' => $providerName
                ]);
                $this->triedProviders[] = $providerName;
            }

            // Si on a bouclé sur tous les fournisseurs sans en trouver un disponible
            if ($this->currentIndex % count($this->providers) === $startIndex % count($this->providers)) {
                $this->logger->critical('Aucun fournisseur de géolocalisation disponible');
                throw new \RuntimeException('Aucun fournisseur de géolocalisation disponible');
            }

        } while ($provider === null);

        return $provider;
    }

    /**
     * Marque un fournisseur comme ayant été essayé
     */
    public function markProviderTried(GeolocationProviderInterface $provider): void
    {
        $providerName = $provider->getName();
        if (!in_array($providerName, $this->triedProviders)) {
            $this->triedProviders[] = $providerName;
        }
    }

    /**
     * Réinitialise la liste des fournisseurs essayés
     */
    public function resetTriedProviders(): void
    {
        $this->triedProviders = [];
    }

    /**
     * Permet de réinitialiser l'index du provider
     * Utile en mode asynchrone pour garantir que les workers commencent toujours au même point
     */
    public function resetIndex(): void
    {
        $this->currentIndex = 0;
        $this->resetTriedProviders();
    }

    private function selectProviderByIndex(int $index): GeolocationProviderInterface
    {
        return $this->providers[ $index % count($this->providers) ];
    }
}
