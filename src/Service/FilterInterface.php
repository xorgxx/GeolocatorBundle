<?php

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

interface FilterInterface
{
    public function apply(Request $request, array $geoData): bool;
}
