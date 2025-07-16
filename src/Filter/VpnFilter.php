<?php
declare(strict_types=1);

namespace GeolocatorBundle\Filter;

use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Filter that blocks requests coming from VPN connections.
 * Uses the 'is_vpn' field from geolocation data to determine if the request is from a VPN.
 */
final class VpnFilter implements FilterInterface
{
    private bool $enabled;
    private array $allowedIps;
    private LoggerInterface $logger;

    /**
     * @param bool $enabled Whether VPN detection is enabled
     * @param array $allowedIps List of IPs that are allowed even if they are VPNs
     * @param LoggerInterface|null $logger Logger for debugging
     */
    public function __construct(
        bool $enabled = true,
        array $allowedIps = [],
        LoggerInterface $logger = null
    ) {
        $this->enabled = $enabled;
        $this->allowedIps = $allowedIps;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, array $geoData): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $ip = $geoData['ip'] ?? null;
        
        // Skip if IP is in allowed list
        if ($ip && in_array($ip, $this->allowedIps, true)) {
            $this->logger->debug("VPN filter: IP {$ip} is in allowed list, skipping VPN check");
            return false;
        }

        // Check if the request is from a VPN
        $isVpn = $geoData['is_vpn'] ?? false;
        
        if ($isVpn) {
            $this->logger->info("VPN filter: Blocked IP {$ip} identified as VPN");
            return true;
        }

        return false;
    }
}