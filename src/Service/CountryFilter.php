<?php

namespace GeolocatorBundle\Service;

class CountryFilter
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Vérifie si un pays est autorisé en fonction des listes d'allowlist et blocklist.
     */
    public function isAllowed(?string $countryCode): bool
    {
        if ($countryCode === null) {
            // Si le pays est inconnu, on autorise par défaut
            return true;
        }

        $countryCode = strtoupper($countryCode);

        // Vérifier la liste de blocage
        $blockList = $this->config['block'] ?? [];
        if (!empty($blockList) && in_array($countryCode, $blockList)) {
            return false;
        }

        // Vérifier la liste d'autorisation
        $allowList = $this->config['allow'] ?? [];
        if (!empty($allowList)) {
            return in_array($countryCode, $allowList);
        }

        // Si aucune liste n'est définie ou si le pays n'est pas dans la liste de blocage,
        // on autorise par défaut
        return true;
    }

    /**
     * Vérifie si un pays doit être banni de façon permanente.
     */
    public function isPermanentlyBanned(?string $countryCode): bool
    {
        if ($countryCode === null) {
            return false;
        }

        $countryCode = strtoupper($countryCode);
        $permanentCountries = $this->config['permanent_countries'] ?? [];

        return in_array($countryCode, $permanentCountries);
    }
}
