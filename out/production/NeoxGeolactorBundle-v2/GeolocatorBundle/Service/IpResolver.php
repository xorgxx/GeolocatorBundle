<?php

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class IpResolver
{
    private array $trustedHeaders;
    private array $trustedProxies;

    /**
     * @param array $trustedHeaders Les en-têtes de proxy à faire confiance (ex: ['X-Forwarded-For'])
     * @param array $trustedProxies Les adresses IP des proxys à faire confiance
     */
    public function __construct(
        array $trustedHeaders = ['X-Forwarded-For'],
        array $trustedProxies = []
    ) {
        $this->trustedHeaders = $trustedHeaders;
        $this->trustedProxies = $trustedProxies;
    }

    /**
     * Configure les proxys de confiance pour la requête actuelle
     */
    private function configureTrustedProxies(Request $request): void
    {
        // Définir les proxys de confiance si disponibles
        if (!empty($this->trustedProxies)) {
            // Convertir les IP CIDR en liste d'IPs si nécessaire
            $proxies = $this->expandProxyList($this->trustedProxies);

            // Déterminer les en-têtes de confiance
            $headers = 0;
            if (in_array('X-Forwarded-For', $this->trustedHeaders)) {
                $headers |= Request::HEADER_X_FORWARDED_FOR;
            }
            if (in_array('X-Forwarded-Host', $this->trustedHeaders)) {
                $headers |= Request::HEADER_X_FORWARDED_HOST;
            }
            if (in_array('X-Forwarded-Proto', $this->trustedHeaders)) {
                $headers |= Request::HEADER_X_FORWARDED_PROTO;
            }
            if (in_array('X-Forwarded-Port', $this->trustedHeaders)) {
                $headers |= Request::HEADER_X_FORWARDED_PORT;
            }
            if (in_array('X-Forwarded-Prefix', $this->trustedHeaders)) {
                $headers |= Request::HEADER_X_FORWARDED_PREFIX;
            }

            // Configurer les proxys de confiance pour cette requête
            Request::setTrustedProxies($proxies, $headers);
        }
    }

    /**
     * Expande la liste des proxys, en gérant les notations CIDR
     */
    private function expandProxyList(array $proxyList): array
    {
        $expanded = [];

        foreach ($proxyList as $proxy) {
            // Si c'est une plage CIDR, on l'ajoute telle quelle (Symfony gère les CIDR)
            // Sinon on ajoute juste l'IP
            $expanded[] = $proxy;
        }

        return $expanded;
    }

    /**
     * Récupère l'adresse IP du client de manière sécurisée
     * 
     * Cette méthode configure d'abord les proxys de confiance,
     * puis utilise la méthode getClientIp() de Symfony qui applique
     * automatiquement la logique de validation des en-têtes X-Forwarded-For
     */
    public function getClientIp(Request $request): string
    {
        // Configurer les proxys de confiance pour cette requête
        $this->configureTrustedProxies($request);

        // Utiliser la méthode sécurisée de Symfony pour obtenir l'IP cliente
        $ip = $request->getClientIp();

        return $ip ?: '127.0.0.1';
    }
}
