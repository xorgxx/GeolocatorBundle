<?php

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class IpResolver
{
    // No need for manual proxy configuration
    public function resolve(Request $request): ?string
    {
        // Check X-Forwarded-For header
        $xff = $request->headers->get('X-Forwarded-For');
        if ($xff) {
            $ips = array_map('trim', explode(',', $xff));
            foreach ($ips as $candidate) {
                // Skip private and reserved ranges
                if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $candidate;
                }
            }
        }
        // Fallback to remote address
        return $request->server->get('REMOTE_ADDR');
    }
}
