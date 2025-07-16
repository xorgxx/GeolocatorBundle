<?php
namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class IpResolver
{
    private array $trustedHeaders;

    public function __construct(array $trustedHeaders = ['X-Forwarded-For', 'Client-Ip'])
    {
        $this->trustedHeaders = $trustedHeaders;
    }

    public function resolve(Request $request): ?string
    {
        foreach ($this->trustedHeaders as $header) {
            if ($request->headers->has($header)) {
                $ipList = explode(',', $request->headers->get($header));
                $ip = trim(reset($ipList));
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $request->getClientIp();
    }
}
