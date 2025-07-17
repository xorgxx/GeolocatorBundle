<?php

namespace GeolocatorBundle\EventListener;

use GeolocatorBundle\Attribute\GeoFilter;
use GeolocatorBundle\Model\BanResult;
use GeolocatorBundle\Service\GeolocatorService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listener qui traite les attributs GeoFilter sur les contrôleurs
 * et applique les règles de filtrage géographique.
 */
class GeoFilterListener implements EventSubscriberInterface
{
    private GeolocatorService $geolocator;
    private ?string           $globalRedirectUrl;

    public function __construct(GeolocatorService $geolocator, ?string $globalRedirectUrl = null)
    {
        $this->geolocator = $geolocator;
        $this->globalRedirectUrl = $globalRedirectUrl;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => [
                'onKernelController',
                10
            ],
            // Priorité plus haute que le listener GeolocatorListener
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $controller = $event->getController();

        // Gestion des contrôleurs sous forme de tableau [classe, méthode]
        if (!is_array($controller)) {
            return;
        }

        [
            $controllerObject,
            $methodName
        ] = $controller;

        // Récupérer les attributs GeoFilter sur la classe et la méthode
        $reflectionClass = new \ReflectionClass($controllerObject);
        $reflectionMethod = $reflectionClass->getMethod($methodName);

        $classAttributes = $reflectionClass->getAttributes(GeoFilter::class);
        $methodAttributes = $reflectionMethod->getAttributes(GeoFilter::class);

        // Priorité aux attributs de méthode par rapport aux attributs de classe
        $geoFilterAttribute = null;

        if (!empty($classAttributes)) {
            $geoFilterAttribute = $classAttributes[ 0 ]->newInstance();
        }

        if (!empty($methodAttributes)) {
            $geoFilterAttribute = $methodAttributes[ 0 ]->newInstance();
        }

        if ($geoFilterAttribute === null) {
            return; // Pas d'attribut GeoFilter, on ne fait rien
        }

        // Obtenir la configuration spécifique de l'attribut
        $attributeConfig = $geoFilterAttribute->toArray();
        $request = $event->getRequest();

        // Créer un processeur temporaire avec les configurations de l'attribut
        $result = $this->processRequestWithConfig($request, $attributeConfig);

        // Si la requête est bloquée, on redirige
        if ($result->isBanned()) {
            $redirectUrl = $geoFilterAttribute->redirectUrl ?? $this->globalRedirectUrl ?? '/blocked';
            $this->blockAccess($event, $redirectUrl);
        }
    }

    /**
     * Traite une requête avec une configuration spécifique
     * Méthode qui fusionne la configuration globale avec la configuration de l'attribut
     */
    private function processRequestWithConfig(Request $request, array $attributeConfig): BanResult
    {
        // Obtenir l'IP cliente
        $ip = $this->geolocator->getClientIp($request);

        try {
            // Obtenir les informations de géolocalisation
            $geoLocation = $this->geolocator->getGeoLocationFromRequest($request);
            if ($geoLocation === null) {
                // En cas d'erreur de géolocalisation, autoriser par défaut
                return new BanResult(false, 'Error in geolocation, allowing request', $ip);
            }

            // Appliquer les règles de filtrage spécifiques à l'attribut

            // 1. Vérification des filtres IP
            $ipFilters = $attributeConfig[ 'ip_filters' ];
            if ($ipFilters[ 'enabled' ]) {
                // Vérifier les plages d'IP autorisées/bloquées
                $isInAllowList = false;
                $isInBlockList = false;

                // Vérifier d'abord la liste d'autorisation
                if (!empty($ipFilters[ 'allow_list' ])) {
                    foreach ($ipFilters[ 'allow_list' ] as $range) {
                        if (IpUtils::checkIp($ip, $range)) {
                            $isInAllowList = true;
                            break;
                        }
                    }

                    // Si la liste d'autorisation est exclusive et l'IP n'y est pas, bloquer
                    if ($ipFilters[ 'allow_list_exclusive' ] && !$isInAllowList) {
                        return new BanResult(true, 'IP not in allowed list', $ip, $geoLocation);
                    }
                }

                // Vérifier ensuite la liste de blocage
                if (!empty($ipFilters[ 'block_list' ])) {
                    foreach ($ipFilters[ 'block_list' ] as $range) {
                        if (IpUtils::checkIp($ip, $range)) {
                            $isInBlockList = true;
                            break;
                        }
                    }

                    if ($isInBlockList) {
                        return new BanResult(true, 'IP in blocked list', $ip, $geoLocation);
                    }
                }
            }

            // 2. Vérification du pays
            $countryCode = $geoLocation->getCountryCode();
            $countryFilters = $attributeConfig[ 'country_filters' ];

            if ($countryCode) {
                // Vérifier si le pays est dans la liste des pays bloqués
                if (!empty($countryFilters[ 'block' ]) && in_array($countryCode, $countryFilters[ 'block' ])) {
                    return new BanResult(true, 'Country blocked: ' . $countryCode, $ip, $geoLocation);
                }

                // Vérifier si le pays est dans la liste des pays autorisés (s'il y en a)
                if (!empty($countryFilters[ 'allow' ]) && !in_array($countryCode, $countryFilters[ 'allow' ])) {
                    return new BanResult(true, 'Country not allowed: ' . $countryCode, $ip, $geoLocation);
                }
            }

            // 3. Vérification VPN/Proxy
            $vpnConfig = $attributeConfig[ 'vpn_detection' ];
            if ($vpnConfig[ 'enabled' ]) {
                // Si l'IP est dans la liste des IPs VPN autorisées, on l'autorise quand même
                if (!empty($vpnConfig[ 'allowed_ips' ]) && in_array($ip, $vpnConfig[ 'allowed_ips' ])) {
                    return new BanResult(false, 'VPN/Proxy allowed by configuration', $ip, $geoLocation);
                }

                // Sinon on vérifie si c'est un VPN
                if ($geoLocation->isVpn()) {
                    return new BanResult(true, 'VPN/Proxy detected', $ip, $geoLocation);
                }
            }

            // 4. Vérification des crawlers
            if (isset($attributeConfig[ 'crawler_filter' ]) && is_array($attributeConfig[ 'crawler_filter' ])) {
                $crawlerConfig = $attributeConfig[ 'crawler_filter' ];

                if (isset($crawlerConfig[ 'enabled' ]) && $crawlerConfig[ 'enabled' ]) {
                    // Utiliser le getter pour accéder à la propriété privée
                    $isCrawler = $this->geolocator->getCrawlerFilter()
                                                  ->isCrawler($request, $geoLocation);

                    if ($isCrawler) {
                        $shouldBlock = isset($crawlerConfig[ 'allow_known' ]) ? !$crawlerConfig[ 'allow_known' ] : true;
                        if ($shouldBlock) {
                            return new BanResult(true, 'Crawler détecté', $ip, $geoLocation);
                        }
                    }
                }
            }

            // 5. Rate Limiting (si activé)
            // Note: implémentation simplifiée, à adapter selon le système de rate limiting
            $rateLimiter = $attributeConfig[ 'rate_limiter' ];
            if ($rateLimiter[ 'enabled' ]) {
                // Code pour le rate limiting...
            }

            // Tout est OK, on autorise la requête
            return new BanResult(false, 'Request allowed by attribute rules', $ip, $geoLocation);

        } catch (\Exception $e) {
            // En cas d'erreur, on autorise par défaut
            return new BanResult(false, 'Error in attribute filter: ' . $e->getMessage(), $ip);
        }
    }

    /**
     * Bloque l'accès et redirige si nécessaire
     */
    private function blockAccess(ControllerEvent $event, ?string $redirectUrl = null): void
    {
        $url = $redirectUrl ?: $this->globalRedirectUrl ?: '/blocked';
        $response = new RedirectResponse($url);
        $event->setController(function () use ($response) {
            return $response;
        });
    }
}
