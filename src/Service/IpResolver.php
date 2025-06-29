<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Résout l’adresse IP cliente, en tenant compte de l’en-tête X-Forwarded-For
 * et des plages privées/réservées.
 */
final class IpResolver
{
    /** @var int Flags pour exclure les plages privées et réservées */
    private int $filterFlags;

    /**
     * @param int $filterFlags FILTER_FLAG_* valides pour filter_var()
     */
    public function __construct(int $filterFlags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
    {
        $this->filterFlags = $filterFlags;
    }

    /**
     * Retourne la première IP publique valide du X-Forwarded-For,
     * ou la REMOTE_ADDR si aucune n’est trouvée.
     *
     * @param Request $request Requête HTTP en cours.
     * @return string|null     IP cliente ou null si non déterminable.
     */
    public function resolve(Request $request): ?string
    {
        // Symfony gère déjà les proxies de confiance si configurés :
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
     * Extrait la première IP publique valide d’une chaîne XFF.
     *
     * @param string $xff Valeur brute de l’en-tête X-Forwarded-For.
     * @return string|null
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
     * Vérifie qu’une IP est valide et hors des plages privées/réservées.
     *
     * @param string $ip
     * @return bool
     */
    private function isValidPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $this->filterFlags);
    }
}
