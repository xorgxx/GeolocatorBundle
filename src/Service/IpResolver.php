<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the client IP address, taking into account the X-Forceded-For header
 * and private/reserved beaches.
 */
final class IpResolver
{
    /** @var int Flags to exclude private and reserved ranges */
    private int $filterFlags;

    /**
     * @param int $filterFlags Valid FILTER_FLAG_* for filter_var()
     */
    public function __construct(int $filterFlags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
    {
        $this->filterFlags = $filterFlags;
    }

    /**
     * Return the first public IP valid of X-Forceded-For,
     * or Remote_Addr if none is found.
     *
     * @Param Request $Request HTTP request in progress.
     * @RETURN String | Null IP CLIENTE or NULL if not determinable.
     */
    public function resolve(Request $request): ?string
    {
        // Symfony already manages the confidence proxies if configured:
        if (method_exists($request, 'getClientIps')) {
            $ips = $request->getClientIps();
            if (!empty($ips) && $this->isValidPublicIp($ips[0])) {
                return $ips[0];
            }
        }

        // Fallback : parser manuellement X-Forwarded-For
        $xff = $request->headers->get('X-Forwarded-For', '');
        if ($xff !== '') {
            $publicIp = $this->extractPublicIpFromXff($xff);
            if ($publicIp !== null) {
                return $publicIp;
            }
        }

        $remote = $request->server->get('REMOTE_ADDR');
        return $this->isValidPublicIp((string)$remote) ? $remote : null;
    }

    /**
     * Extract the first public IP valid from an XFF channel.
     *
     * @Param String $XFF gross value of the X-Forceded-For header.
     * @return string | null
     */
    private function extractPublicIpFromXff(string $xff): ?string
    {
        foreach (array_map('trim', explode(',', $xff)) as $ip) {
            if ($this->isValidPublicIp($ip)) {
                return $ip;
            }
        }
        return null;
    }

    /**
     * Check that an IP is valid and outside the private/reserved beaches.
     *
     * @param string $ip
     * @return bool
     */
    private function isValidPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $this->filterFlags);
    }
}
