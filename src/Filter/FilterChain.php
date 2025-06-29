<?php
declare(strict_types=1);

namespace GeolocatorBundle\Filter;

use Symfony\Component\HttpFoundation\Request;

/**
 * Parcourt tous les services taggés FilterInterface
 * et renvoie le premier FilterResult bloquant (ou null si tous passent).
 */
final class FilterChain
{
    /** @var iterable<FilterInterface> */
    private iterable $filters;

    /**
     * @param iterable<FilterInterface> $filters Injecté via tagged_iterator neox.geofilter.filter
     */
    public function __construct(iterable $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Applique tous les filtres et renvoie un résultat bloquant,
     * ou null si aucun filtre ne bloque.
     */
    public function process(Request $request, array $geoData): ?FilterResult
    {
        $country = $geoData['country'] ?? null;

        foreach ($this->filters as $filter) {
            if ($filter->apply($request, $geoData)) {
                return new FilterResult(
                    true,
                    'Blocked by ' . \get_class($filter),
                    $country
                );
            }
        }

        return null;
    }
}
