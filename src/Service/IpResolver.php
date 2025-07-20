<?php

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

class IpResolver
{
    private array $trustedHeaders;
    private array $trustedProxies;
    private bool $devMode;


    /**
     * @param array $trustedHeaders Les en-têtes de proxy à faire confiance (ex: ['X-Forwarded-For'])
     * @param array $trustedProxies Les adresses IP des proxys à faire confiance
     */
    public function __construct(
        array $trustedHeaders = ['X-Forwarded-For'],
        array $trustedProxies = [],
        bool $environment = false
    ) {
        $this->trustedHeaders   = $trustedHeaders;
        $this->trustedProxies   = $trustedProxies;
        $this->devMode          = $environment ;

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

        // En mode développement, essayer d'obtenir la vraie IP externe
        if ($this->devMode && ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.'))) {
            return $this->getExternalIp($request);
        }
        return $ip ?: '127.0.0.1';
    }


    /**
     * Récupère l'IP externe en mode développement
     */
    private function getExternalIp(Request $request): string
    {
        // 1. Vérifier s'il y a un en-tête X-Forwarded-For personnalisé pour les tests
        if ($request->headers->has('X-Real-IP')) {
            return $request->headers->get('X-Real-IP');
        }

        // 2. Essayer de récupérer l'IP via un service externe (seulement en dev)
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3, // timeout court
                    'method' => 'GET',
                    'user_agent' => 'Mozilla/5.0 (compatible; GeolocatorBundle/1.0)'
                ]
            ]);

            // Utiliser un service fiable pour récupérer l'IP publique
            $externalIp = @file_get_contents('https://api.ipify.org', false, $context);

            if ($externalIp && filter_var(trim($externalIp), FILTER_VALIDATE_IP)) {
                return trim($externalIp);
            }

            // Fallback avec un autre service
            $externalIp = @file_get_contents('https://icanhazip.com', false, $context);
            if ($externalIp && filter_var(trim($externalIp), FILTER_VALIDATE_IP)) {
                return trim($externalIp);
            }

        } catch (\Exception $e) {
            // Ignorer les erreurs et utiliser l'IP locale
        }

        return '127.0.0.1';
    }

}
