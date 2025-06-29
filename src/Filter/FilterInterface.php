<?php
// src/Filter/FilterInterface.php
declare(strict_types=1);

namespace GeolocatorBundle\Filter;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an IP/Geo filter that decides if a request should be blocked.
 */
interface FilterInterface
{
    /**
     * Applies the filter to a given request.
     *
     * @param Request $request The current HTTP request.
     * @param array $geoData Geolocation data (country, continent, is_vpn, asn, isp...).
     *
     * @return bool  true to block the request, false to let it pass.
     */
    public function apply(Request $request, array $geoData): bool;
}
