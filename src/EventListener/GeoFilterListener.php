<?php

namespace GeolocatorBundle\EventListener;

use Exception;
use GeolocatorBundle\Event\GeoFilterBlockedEvent;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Service\FilterInterface;
use GeolocatorBundle\Service\GeolocationCache;
use GeolocatorBundle\Service\IpResolver;
use GeolocatorBundle\Service\BotDetector;

class GeoFilterListener
{
    private IpResolver $ipResolver;
    private GeolocationCache $geolocationCache;
    private BanManager $banManager;
    private BotDetector $botDetector;
    private RateLimiterFactory $rateLimiter;
    private EventDispatcherInterface $dispatcher;
    private RouterInterface $router;
    private array $config;

    /** @var FilterInterface[] */
    private iterable $filters;

    /**
     * @param FilterInterface[] $filters  all services tagged with 'geofilter.filter'
     */
    public function __construct(
        IpResolver $ipResolver,
        GeolocationCache $geolocationCache,
        BanManager $banManager,
        BotDetector $botDetector,
        RateLimiterFactory $rateLimiter,
        EventDispatcherInterface $dispatcher,
        RouterInterface $router,
        array $config,
        iterable $filters
    ) {
        $this->ipResolver       = $ipResolver;
        $this->geolocationCache = $geolocationCache;
        $this->banManager       = $banManager;
        $this->botDetector      = $botDetector;
        $this->rateLimiter      = $rateLimiter;
        $this->dispatcher       = $dispatcher;
        $this->router           = $router;
        $this->config           = $config;
        $this->filters          = $filters;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $ip      = $this->ipResolver->resolve($request);

        if (!$ip) {
            if (!empty($this->config['simulate'])) {
                return;
            }
            $event->setResponse(new Response('Unable to determine IP', 400));
            return;
        }

        // Bypass explicitly allowed ranges
        if (!empty($this->config['allowedRanges']) && IpUtils::checkIp($ip, $this->config['allowedRanges'])) {
            return;
        }

        // Check existing ban
        if ($this->banManager->isBanned($ip)) {
            $event->setResponse($this->buildResponse());
            return;
        }

        // Static IP filtering
        if (!empty($this->config['blockedIps']) && in_array($ip, $this->config['blockedIps'], true)) {
            $this->banAndRespond($ip, 'Blocked IP');
            return;
        }
        if (!empty($this->config['blockedRanges'])
            && IpUtils::checkIp($ip, $this->config['blockedRanges'])
        ) {
            $this->banAndRespond($ip, 'Blocked range');
            return;
        }

        // Geolocation
        $data     = $this->geolocationCache->locate($ip);
        $country  = $data['countryCode']   ?? null;
        $continent= $data['continentCode'] ?? null;
        $asn      = $data['asn']           ?? null;
        $isp      = $data['isp']           ?? null;

        // Countries / continents
        if (!empty($this->config['allowedCountries']) && in_array($country, $this->config['allowedCountries'], true)) {
            return;
        }
        if (!empty($this->config['blockedCountries']) && in_array($country, $this->config['blockedCountries'], true)) {
            $this->banAndRespond($ip, 'Blocked country');
            return;
        }
        if (!empty($this->config['allowedContinents']) && in_array($continent, $this->config['allowedContinents'], true)) {
            return;
        }
        if (!empty($this->config['blockedContinents']) && in_array($continent, $this->config['blockedContinents'], true)) {
            $this->banAndRespond($ip, 'Blocked continent');
            return;
        }

        // ASN / ISP
        if (!empty($this->config['allowedAsns']) && in_array($asn, $this->config['allowedAsns'], true)) {
            return;
        }
        if (!empty($this->config['blockedAsns']) && in_array($asn, $this->config['blockedAsns'], true)) {
            $this->banAndRespond($ip, 'Blocked ASN');
            return;
        }
        if (!empty($this->config['allowedIsps']) && in_array($isp, $this->config['allowedIsps'], true)) {
            return;
        }
        if (!empty($this->config['blockedIsps']) && in_array($isp, $this->config['blockedIsps'], true)) {
            $this->banAndRespond($ip, 'Blocked ISP');
            return;
        }

        // VPN
        if (($this->config['requireNonVPN'] ?? false) && !empty($data['proxy'])) {
            $this->banAndRespond($ip, 'VPN detected');
            return;
        }

        // Crawler/User-Agent
        $ua = $request->headers->get('User-Agent', '');
        foreach ($this->config['blocked_crawlers'] ?? [] as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                $this->banAndRespond($ip, 'Crawler detected');
                return;
            }
        }

        // Flood / Ping
        $limiter = $this->rateLimiter->create($ip);
        if (!$limiter->consume()->isAccepted()) {
            $this->banAndRespond($ip, 'Flood detected');
            return;
        }

        // Custom filters
        foreach ($this->filters as $filter) {
            if ($filter->apply($request, $data)) {
                $this->banAndRespond($ip, 'Filter '.get_class($filter));
                return;
            }
        }

        // No blocking → allow access
    }

    /**
     * @throws Exception
     */
    private function banAndRespond(string $ip, string $reason): void
    {
        $this->banManager->addBan($ip, $reason, $this->config['ban_duration'] ?? '1 hour');
        $eventResponse = $this->buildResponse();
        $this->dispatcher->dispatch(
        // You may define an event for logging/webhook
            new GeoFilterBlockedEvent($ip, $reason, null)
        );
        // Note: $event is not available here,
        // you should pass the RequestEvent as a parameter if needed
        // or build and throw an HTTP exception.
        // Simplified example:
        throw new HttpException(
            $eventResponse->getStatusCode(), $reason
        );
    }

    private function buildResponse(): Response
    {
        if (!empty($this->config['use_custom_blocked_page'])) {
            return new Response(
                $this->router->generate($this->config['redirect_route']), 403
            );
        }
        return new RedirectResponse(
            $this->router->generate($this->config['redirect_route'])
        );
    }
}