<?php
// src/Filter/FilterChain.php
declare(strict_types=1);

namespace GeolocatorBundle\Filter;

use Symfony\Component\HttpFoundation\Request;

/**
 * Processes all services tagged with FilterInterface
 * and returns the first blocking FilterResult (or null if all pass).
 */
final class FilterChain
{
    /** @var iterable<FilterInterface> */
    private iterable $filters;

    /**
     * @param iterable<FilterInterface> $filters Injected via tagged_iterator neox.geofilter.filter
     */
    public function __construct(iterable $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Applies all filters and returns a FilterResult if blocking,
     * or null if no filter blocks.
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
