<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Event\GeolocatorEvent;
use GeolocatorBundle\Model\BanResult;
use GeolocatorBundle\Model\GeoLocation;
use GeolocatorBundle\Storage\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class GeolocatorService
{
    private array                     $providers;
    private array                     $fallbackProviders;
    private string                    $defaultProvider;
    private StorageInterface          $storage;
    private BanManager                $banManager;
    private CountryFilter             $countryFilter;
    private IpFilter                  $ipFilter;
    private VpnDetector               $vpnDetector;
    private CrawlerFilter             $crawlerFilter;
    private LoggerInterface           $logger;
    private array                     $config;
    private ?EventDispatcherInterface $eventDispatcher;
    private ?string                   $eventBridgeService;
    private IpResolver                $ipResolver;
    private ?AsyncManager             $asyncManager;

    public function __construct(array $providers, StorageInterface $storage, BanManager $banManager, CountryFilter $countryFilter, IpFilter $ipFilter, VpnDetector $vpnDetector, CrawlerFilter $crawlerFilter, LoggerInterface $logger, array $config, IpResolver $ipResolver, ?EventDispatcherInterface $eventDispatcher = null, ?AsyncManager $asyncManager = null)
    {
        $this->providers = $providers;
        $this->storage = $storage;
        $this->banManager = $banManager;
        $this->countryFilter = $countryFilter;
        $this->ipFilter = $ipFilter;
        $this->vpnDetector = $vpnDetector;
        $this->crawlerFilter = $crawlerFilter;
        $this->logger = $logger;
        $this->config = $config;
        $this->ipResolver = $ipResolver;
        $this->eventDispatcher = $eventDispatcher;
        $this->defaultProvider = $config['providers']['default'] ?? 'ipapi';
        $this->fallbackProviders = $config['providers']['fallback'] ?? [];
        $this->eventBridgeService = $config['event_bridge_service'] ?? null;
        $this->asyncManager = $asyncManager;

        // Vérifier si nous sommes en mode fallback avec le provider local
        if (isset($config['provider_fallback_mode']) && $config['provider_fallback_mode'] === true) {
            $this->logger->info('GeolocatorService initialisé en mode de secours avec le provider local');
        }
    }

    public function getBanManager(): BanManager
    {
        return $this->banManager;
    }



    /**
     * Traite une requête HTTP et vérifie si elle doit être bloquée.
     */
    public function processRequest(Request $request): BanResult
    {
        if (!$this->config['enabled']) {
            return new BanResult(false, 'Geolocator disabled');
        }

        $ip = $this->getClientIp($request);

        // Vérifier si déjà banni
        if ($this->banManager->isBanned($ip)) {
            $banInfo = $this->banManager->getBanInfo($ip);
            $expiration = $banInfo['expiration'] ?? null;
            $expiration = $expiration ? new \DateTime($expiration) : null;

            $result = new BanResult(true, $banInfo['reason'] ?? 'IP already banned', $ip, null, $expiration);

            $this->dispatchEvent('ban.detected', $result);
            return $result;
        }

        try {
            // Obtenir la géolocalisation
            $geoLocation = $this->getGeoLocation($ip);

            // Filtrer les crawlers
            if (($this->config['crawler_filter'] ?? false) && ($this->config['crawler_filter']['enabled'] ?? false)) {
                $detectionResult = $this->crawlerFilter->detectCrawler($request, $geoLocation);

                if ($detectionResult['isCrawler']) {
                    $allowKnown = $this->config['crawler_filter']['allow_known'] ?? false;
                    $shouldBlock = $detectionResult['isKnown']
                        ? !$allowKnown
                        : true; // Par défaut, bloquer les crawlers non connus

                    if ($shouldBlock) {
                        $crawlerName = $detectionResult['name'] ?? '';
                        $reason = 'Crawler détecté' . ($crawlerName ? ' (' . $crawlerName . ')' : '');
                        return $this->handleBan($ip, $reason, $geoLocation);
                    }
                }
            }

            // Filtrer par pays
            if (!$this->countryFilter->isAllowed($geoLocation->getCountryCode())) {
                $isPermanent = $this->countryFilter->isPermanentlyBanned($geoLocation->getCountryCode());
                return $this->handleBan($ip, 'Country blocked: ' . $geoLocation->getCountryCode(), $geoLocation, $isPermanent);
            }

            // Détecter VPN
            if ($this->vpnDetector->isVpn($ip, $geoLocation)) {
                return $this->handleBan($ip, 'VPN/Proxy detected', $geoLocation);
            }
            
            // La requête est autorisée
            $result = new BanResult(false, 'Request allowed', $ip, $geoLocation);
            $this->dispatchEvent('request.allowed', $result);
            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Geolocator error: ' . $e->getMessage(), [ 'ip' => $ip ]);

            // En cas d'erreur, on laisse passer par défaut
            return new BanResult(false, 'Error occurred, allowing request', $ip);
        }
    }

    /**
     * Récupère l'adresse IP du client à partir de la requête de manière sécurisée.
     */
    public function getClientIp(Request $request): string
    {
        return $this->ipResolver->getClientIp($request);
    }

    /**
     * Vérifie si une adresse IP est autorisée selon les règles de filtrage IP
     */
    public function isIpAllowed(string $ip): bool
    {
        return $this->ipFilter->isAllowed($ip);
    }

    /**
     * Récupère le service de filtrage des crawlers.
     *
     * @return CrawlerFilter
     */
    public function getCrawlerFilter(): CrawlerFilter
    {
        return $this->crawlerFilter;
    }

    public function isSimulationMode()
    {
        return $this->config['simulate'] ?? false;
    }

    public function getIpFilter(): IpFilter
    {
        return $this->ipFilter;
    }


    /**
     * Récupère les informations de géolocalisation à partir d'une requête HTTP.
     *
     * @param Request $request La requête HTTP
     * @param bool $useAsync Utiliser le mode asynchrone si disponible
     * @return GeoLocation|null Les informations de géolocalisation ou null en cas d'erreur
     */
    public function getGeoLocationFromRequest(Request $request, bool $useAsync = false): ?GeoLocation
    {
        try {
            $ip = $this->getClientIp($request);

            // Vérifier si on veut utiliser le mode asynchrone
            if ($useAsync && $this->asyncManager !== null && $this->asyncManager->isAsyncAvailable()) {
                // Essayer d'envoyer en asynchrone
                $dispatched = $this->asyncManager->dispatchGeolocationTask($ip);

                if ($dispatched) {
                    $this->logger->info('Demande de géolocalisation envoyée en asynchrone', ['ip' => $ip]);
                    // Retourner null ou une géolocalisation par défaut
                    return null;
                }
            }

            // Mode synchrone (par défaut ou fallback si l'asynchrone échoue)
            return $this->getGeoLocation($ip);
        } catch (\Exception $e) {
            $this->logger->error('Géolocalisation depuis requête échouée: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Détermine si le mode asynchrone est disponible
     */
    public function isAsyncAvailable(): bool
    {
        return $this->asyncManager !== null && $this->asyncManager->isAsyncAvailable();
    }

    /**
     * Récupère les informations de géolocalisation pour une adresse IP.
     */
    public function getGeoLocation(string $ip): GeoLocation
    {
        // Vérifier si nous sommes en mode fallback avec le provider local
        if (($this->config['provider_fallback_mode'] ?? false) === true) {
            // En mode fallback, utiliser directement le provider local
            if (isset($this->providers['local'])) {
                $this->logger->info("Utilisation du provider local en mode de secours pour l'IP: {$ip}");
                return $this->providers['local']->getGeoLocation($ip);
            }
        }

        // Essayer le provider par défaut
        if (isset($this->providers[$this->defaultProvider])) {
            try {
                return $this->providers[$this->defaultProvider]->getGeoLocation($ip);
            } catch (\Exception $e) {
                $this->logger->warning('Default provider failed: ' . $e->getMessage());
            }
        }

        // Essayer les providers de fallback
        foreach ($this->fallbackProviders as $providerName) {
            if (isset($this->providers[$providerName])) {
                try {
                    return $this->providers[$providerName]->getGeoLocation($ip);
                } catch (\Exception $e) {
                    $this->logger->warning("Fallback provider $providerName failed: " . $e->getMessage());
                }
            }
        }

        // Si tous les providers ont échoué et que le provider local est disponible, l'utiliser en dernier recours
        if (isset($this->providers['local'])) {
            $this->logger->warning("Tous les providers externes ont échoué, utilisation du provider local pour l'IP: {$ip}");
            return $this->providers['local']->getGeoLocation($ip);
        }

        throw new \RuntimeException('All providers failed and no local fallback available');
    }

    /**
     * Gère le bannissement d'une IP.
     */
    private function handleBan(string $ip, string $reason, ?GeoLocation $geoLocation = null, bool $permanent = false): BanResult
    {
        if ($this->config['simulate']) {
            $this->logger->info("SIMULATE: Would ban IP $ip for reason: $reason");
            $result = new BanResult(false, "SIMULATE: $reason", $ip, $geoLocation);
            $this->dispatchEvent('ban.simulated', $result);
            return $result;
        }

        $expiration = $this->banManager->banIp($ip, $reason, $permanent);
        $this->logger->warning("IP banned: $ip for reason: $reason");

        $result = new BanResult(true, $reason, $ip, $geoLocation, $expiration);
        $this->dispatchEvent('ban.added', $result);

        return $result;
    }

    /**
     * Dispatche un événement via EventDispatcher ou via un service bridge.
     */
    private function dispatchEvent(string $eventName, BanResult $result): void
    {
        // Si un service de bridge d'événement est configuré, l'utiliser
        if ($this->eventBridgeService !== null) {
            try {
                $bridge = null; // Remplacé par l'appel au service container dans le GeolocatorListener
                if ($bridge && method_exists($bridge, 'dispatchEvent')) {
                    $bridge->dispatchEvent($eventName, $result);
                    return;
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to dispatch event via bridge: ' . $e->getMessage());
            }
        }

        // Utiliser l'EventDispatcher si disponible
        if ($this->eventDispatcher !== null) {
            $event = new GeolocatorEvent($result);
            $this->eventDispatcher->dispatch($event, 'geolocator.' . $eventName);
        }
    }
}
