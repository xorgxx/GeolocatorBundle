<?php

namespace GeolocatorBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class GeoFilter
{
    public function __construct(
        public array $allowedCountries = [],
        public array $blockedCountries = [],
        public array $allowedRanges = [],
        public array $blockedRanges = [],
        public array $blockedIps = [],
        public array $allowedContinents = [],
        public array $blockedContinents = [],
        public bool $requireNonVPN = false,
        public int $pingThreshold = 0,
        public bool $simulate = false,
        public ?string $forceProvider = null
    ) {}
}
