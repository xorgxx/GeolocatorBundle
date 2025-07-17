<?php

namespace GeolocatorBundle\Service;

use Psr\Log\LoggerInterface;

class IpFilter
{
    private array $config;
    private LoggerInterface $logger;

    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Vérifie si une adresse IP est autorisée selon les règles configurées
     * 
     * @param string $ip L'adresse IP à vérifier
     * @param array|null $tempConfig Configuration temporaire à utiliser au lieu de la config par défaut
     * @return bool True si l'IP est autorisée, false sinon
     */
    public function isAllowed(string $ip, ?array $tempConfig = null): bool
    {
        // Utiliser la configuration temporaire si fournie, sinon utiliser la config par défaut
        $config = $tempConfig ?? $this->config;

        // Si les listes sont vides, tout est autorisé par défaut
        if (empty($config['allow_list']) && empty($config['block_list'])) {
            return true;
        }

        // Vérifier d'abord la liste d'autorisation (prioritaire)
        if (!empty($config['allow_list'])) {
            foreach ($config['allow_list'] as $allowedIp) {
                if ($this->ipMatches($ip, $allowedIp)) {
                    $this->logger->info(sprintf('IP %s explicitement autorisée (correspondance avec %s)', $ip, $allowedIp));
                    return true;
                }
            }

            // Si une liste d'autorisation est définie et que l'IP n'y figure pas,
            // l'IP est bloquée par défaut (liste blanche)
            if ($config['allow_list_exclusive'] ?? false) {
                $this->logger->info(sprintf('IP %s bloquée car ne figurant pas dans la liste d\'autorisation exclusive', $ip));
                return false;
            }
        }

        // Vérifier ensuite la liste de blocage
        if (!empty($config['block_list'])) {
            foreach ($config['block_list'] as $blockedIp) {
                if ($this->ipMatches($ip, $blockedIp)) {
                    $this->logger->info(sprintf('IP %s explicitement bloquée (correspondance avec %s)', $ip, $blockedIp));
                    return false;
                }
            }
        }

        // Par défaut, si l'IP n'est pas dans la liste de blocage, elle est autorisée
        return true;
    }

    /**
     * Vérifie si une IP correspond à un pattern (IP exacte ou plage CIDR)
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Correspondance exacte
        if ($ip === $pattern) {
            return true;
        }

        // Correspondance avec plage CIDR (ex: 192.168.1.0/24)
        if (strpos($pattern, '/') !== false) {
            return $this->ipMatchesCidr($ip, $pattern);
        }

        // Correspondance avec wildcard (ex: 192.168.1.*)
        if (strpos($pattern, '*') !== false) {
            return $this->ipMatchesWildcard($ip, $pattern);
        }

        return false;
    }

    /**
     * Vérifie si une IP correspond à une plage CIDR
     */
    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        list($subnet, $bits) = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        $subnetLong &= $mask;

        return ($ipLong & $mask) === $subnetLong;
    }

    /**
     * Vérifie si une IP correspond à un pattern avec wildcard
     */
    private function ipMatchesWildcard(string $ip, string $pattern): bool
    {
        $pattern = str_replace('*', '\d+', $pattern);
        $pattern = '/^' . str_replace('.', '\.', $pattern) . '$/i';

        return (bool) preg_match($pattern, $ip);
    }

    /**
     * Vérifie si l'IP est dans la liste d'autorisation
     * 
     * @param string $ip L'adresse IP à vérifier
     * @param array|null $tempConfig Configuration temporaire à utiliser au lieu de this->config
     * @return bool
     */
    public function isInAllowList(string $ip, ?array $tempConfig = null): bool
    {
        $config = $tempConfig ?? $this->config;

        if (empty($config['allow_list'])) {
            return false;
        }

        foreach ($config['allow_list'] as $allowedIp) {
            if ($this->ipMatches($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'IP est dans la liste de blocage
     * 
     * @param string $ip L'adresse IP à vérifier
     * @param array|null $tempConfig Configuration temporaire à utiliser au lieu de this->config
     * @return bool
     */
    public function isInBlockList(string $ip, ?array $tempConfig = null): bool
    {
        $config = $tempConfig ?? $this->config;

        if (empty($config['block_list'])) {
            return false;
        }

        foreach ($config['block_list'] as $blockedIp) {
            if ($this->ipMatches($ip, $blockedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne la configuration du filtre IP
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
