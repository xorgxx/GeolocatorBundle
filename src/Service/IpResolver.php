<?php

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class IpResolver
{
    private const PUBLIC_IP_FILTER_FLAGS = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

    public function resolve(Request $request): ?string
    {
        $xForwardedFor = $request->headers->get('X-Forwarded-For');
        if ($xForwardedFor) {
            $publicIp = $this->getFirstValidPublicIp($xForwardedFor);
            if ($publicIp !== null) {
                return $publicIp;
            }
        }
        return $request->server->get('REMOTE_ADDR');
    }

    private function getFirstValidPublicIp(string $xForwardedFor): ?string
    {
        $ips = array_map('trim', explode(',', $xForwardedFor));
        foreach ($ips as $candidateIp) {
            if (filter_var($candidateIp, FILTER_VALIDATE_IP, self::PUBLIC_IP_FILTER_FLAGS)) {
                return $candidateIp;
            }
        }
        return null;
    }
}