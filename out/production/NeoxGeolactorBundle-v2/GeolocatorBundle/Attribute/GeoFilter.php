<?php

namespace GeolocatorBundle\Attribute;

/**
 * Attribut permettant de configurer des règles de filtrage géographique 
 * directement sur une classe ou une méthode.
 * 
 * Exemple d'utilisation:
 * #[GeoFilter(allowedCountries: ['FR', 'BE'], blockedCountries: ['RU', 'CN'])]
 * public function securedAction()
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
readonly class GeoFilter
{
    /**
     * @param array $allowedCountries Liste des codes pays autorisés (ex: ['FR', 'BE'])
     * @param array $blockedCountries Liste des codes pays bloqués (ex: ['RU', 'CN'])
     * 
     * @param array $allowedRanges Liste des plages d'IP autorisées (ex: ['192.168.1.0/24'])
     * @param array $blockedRanges Liste des plages d'IP bloquées (ex: ['10.0.0.0/8'])
     * @param bool $ipFiltersEnabled Activer/désactiver les filtres IP
     * @param bool $allowListExclusive Si true, seules les IPs de la liste d'autorisation sont acceptées
     * 
     * @param bool $blockVpn Bloquer les VPN/proxy/Tor
     * @param string|null $vpnProvider Provider à utiliser pour la détection VPN (null = config globale)
     * @param array $vpnAllowedIps Liste des IPs VPN autorisées malgré tout
     * 
     * @param bool $blockCrawlers Bloquer les robots d'indexation
     * @param bool $allowKnownCrawlers Autoriser les crawlers connus (Google, Bing, etc.)
     * 
     * @param bool $rateLimiterEnabled Activer le rate limiter
     * @param int $rateLimiterLimit Nombre de requêtes maximum
     * @param int $rateLimiterInterval Intervalle en secondes
     * 
     * @param bool $cacheEnabled Activer le cache
     * @param int $cacheTtl Durée de vie du cache en secondes
     * 
     * @param string|null $redirectUrl URL de redirection en cas de blocage (null = config globale)
     * @param bool $simulate Mode simulation (ne bloque pas réellement)
     */
    public function __construct(
        // Configuration pays
        public array $allowedCountries = [],
        public array $blockedCountries = [],

        // Configuration IP
        public array $allowedRanges = [],
        public array $blockedRanges = [],
        public bool $ipFiltersEnabled = true,
        public bool $allowListExclusive = false,

        // Configuration VPN
        public bool $blockVpn = true,
        public ?string $vpnProvider = null,
        public array $vpnAllowedIps = [],

        // Configuration crawler
        public bool $blockCrawlers = false,
        public bool $allowKnownCrawlers = true,

        // Configuration rate limiting
        public bool $rateLimiterEnabled = false,
        public int $rateLimiterLimit = 60,
        public int $rateLimiterInterval = 60,

        // Configuration cache
        public bool $cacheEnabled = true,
        public int $cacheTtl = 86400,

        // Autres
        public ?string $redirectUrl = null,
        public bool $simulate = false
    ) {}

    /**
     * Convertit l'attribut en tableau de configuration
     * Pour faciliter la fusion avec la configuration globale
     */
    public function toArray(): array
    {
        return [
            'country_filters' => [
                'allow' => $this->allowedCountries,
                'block' => $this->blockedCountries,
            ],
            'ip_filters' => [
                'enabled' => $this->ipFiltersEnabled,
                'allow_list_exclusive' => $this->allowListExclusive,
                'allow_list' => $this->allowedRanges,
                'block_list' => $this->blockedRanges,
            ],
            'vpn_detection' => [
                'enabled' => $this->blockVpn,
                'provider' => $this->vpnProvider,
                'allowed_ips' => $this->vpnAllowedIps,
            ],
            'crawler_filter' => [
                'enabled' => $this->blockCrawlers,
                'allow_known' => $this->allowKnownCrawlers,
            ],
            'rate_limiter' => [
                'enabled' => $this->rateLimiterEnabled,
                'limit' => $this->rateLimiterLimit,
                'interval' => $this->rateLimiterInterval,
            ],
            'cache' => [
                'enabled' => $this->cacheEnabled,
                'ttl' => $this->cacheTtl,
            ],
            'redirect_on_ban' => $this->redirectUrl,
            'simulate' => $this->simulate,
        ];
    }
}
}
