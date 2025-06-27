<?php

namespace GeolocatorBundle\Export;

class FirewallExporter
{
    public function export(array \$ips, string \$format): string
    {
        return match (\$format) {
            'iptables' => implode("\n", array_map(fn(\$ip) => "iptables -A INPUT -s \$ip -j DROP", \$ips)),
            'nginx' => implode("\n", array_map(fn(\$ip) => "deny \$ip;", \$ips)),
            'csv' => implode("\n", \$ips),
            default => 'Format non supporté',
        };
    }
}
