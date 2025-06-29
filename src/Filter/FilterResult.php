<?php
declare(strict_types=1);

namespace GeolocatorBundle\Filter;

/**
 * Représente le résultat de l’application d’un filtre :
 * - blocked = true si la requête doit être bloquée
 * - reason  = chaîne décrivant la cause
 * - country = code pays associé (optionnel)
 */
final class FilterResult
{
    private bool    $blocked;
    private string  $reason;
    private ?string $country;

    public function __construct(bool $blocked, string $reason = '', ?string $country = null)
    {
        $this->blocked = $blocked;
        $this->reason = $reason;
        $this->country = $country;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }
}
