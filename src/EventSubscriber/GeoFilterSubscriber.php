<?php
declare(strict_types=1);

namespace GeolocatorBundle\EventSubscriber;

use GeolocatorBundle\Service\IpResolver;
use GeolocatorBundle\Service\GeolocatorService;
use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Filter\FilterChain;
use GeolocatorBundle\Event\GeoFilterBlockedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Subscribes geographic filtering to kernel.request
 * and blocks requests according to configured rules.
 */
final class GeoFilterSubscriber implements EventSubscriberInterface
{
    private IpResolver               $ipResolver;
    private GeolocatorService        $geolocatorService;
    private BanManager               $banManager;
    private FilterChain              $filterChain;
    private EventDispatcherInterface $eventDispatcher;
    private array                    $config;

    public function __construct(IpResolver $ipResolver, GeolocatorService $geolocatorService, BanManager $banManager, FilterChain $filterChain, EventDispatcherInterface $eventDispatcher, ParameterBagInterface $params)
    {
        $this->ipResolver = $ipResolver;
        $this->geolocatorService = $geolocatorService;
        $this->banManager = $banManager;
        $this->filterChain = $filterChain;
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $params->get('geolocator') ?? [];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                'onKernelRequest',
                0
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $ip = $this->ipResolver->resolve($request);
        if ($ip === null) {
            return;
        }

        // 1) Already banned?
        if ($this->banManager->isBanned($ip)) {
            $this->banAndRespond($event, $ip, 'IP already banned', null);
            return;
        }

        // 2) Geolocation
        $geoData = $this->geolocatorService->locateIp($ip);

        // 3) Apply centralized filters
        $result = $this->filterChain->process($request, $geoData);
        if (null !== $result && $result->isBlocked()) {
            $this->banAndRespond($event, $ip, $result->getReason(), $result->getCountry());
            return;
        }

        // 4) Country blocking (could be handled as a filter if needed)
        $blockedCountries = $this->config[ 'blocked_countries' ] ?? [];
        $country = $geoData[ 'country' ] ?? null;
        if (null !== $country && \in_array($country, $blockedCountries, true)) {
            $this->banAndRespond($event, $ip, 'Country blocked: ' . $country, $country);
        }
    }

    private function banAndRespond(RequestEvent $event, string $ip, string $reason, ?string $country): void
    {
        $duration = $this->config[ 'ban_duration' ] ?? '1 hour';
        $this->banManager->addBan($ip, $reason, $duration);

        $response = new Response('Forbidden', Response::HTTP_FORBIDDEN);
        $event->setResponse($response);

        $blockedEvent = new GeoFilterBlockedEvent($ip, $reason, $country);
        $this->eventDispatcher->dispatch($blockedEvent);
    }
}