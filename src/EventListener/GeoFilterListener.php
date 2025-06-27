<?php

namespace GeolocatorBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use GeolocatorBundle\Service\IpResolver;
use GeolocatorBundle\Service\GeolocationCache;
use GeolocatorBundle\Service\BanManager;
use GeolocatorBundle\Service\BotDetector;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use GeolocatorBundle\Event\GeoFilterBlockedEvent;

class GeoFilterListener
{
    private IpResolver $ipResolver;
    private GeolocationCache $geolocationCache;
    private BanManager $banManager;
    private BotDetector $botDetector;
    private RateLimiterFactory $rateLimiter;
    private EventDispatcherInterface $dispatcher;
    private array $config;
    private RouterInterface $router;

    public function __construct(
        IpResolver $ipResolver,
        GeolocationCache $geolocationCache,
        BanManager $banManager,
        BotDetector $botDetector,
        RateLimiterFactory $rateLimiter,
        EventDispatcherInterface $dispatcher,
        array $config,
        RouterInterface $router
    ) {
        $this->ipResolver = $ipResolver;
        $this->geolocationCache = $geolocationCache;
        $this->banManager = $banManager;
        $this->botDetector = $botDetector;
        $this->rateLimiter = $rateLimiter;
        $this->dispatcher = $dispatcher;
        $this->config = $config;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $ip = $this->ipResolver->resolve($request);

        if (!$ip) {
            if ($this->config['simulate']) return;
            $event->setResponse(new Response('Impossible de déterminer l’IP', 400));
            return;
        }

        // Rate limiter check
        $limiter = $this->rateLimiter->create($ip);
        $limit = $limiter->consume();
        if (!$limit->isAccepted()) {
            $this->banManager->addBan($ip, 'Flood détecté', $this->config['ban_duration']);
            $event->setResponse($this->buildResponse());
            return;
        }

        // Bot detection
        $ua = $request->headers->get('User-Agent', '');
        if ($this->botDetector->isBot($ua)) {
            if ($this->botDetector->shouldChallenge($ua)) {
                // TODO: issue captcha challenge
            } else {
                $this->banManager->addBan($ip, 'Bot détecté', $this->config['ban_duration']);
                $event->setResponse($this->buildResponse());
                return;
            }
        }

        // Existing filters...
        // At each ban, dispatch event
        // $this->dispatcher->dispatch(new GeoFilterBlockedEvent($ip, 'Reason', $data['countryCode'] ?? null));
    }

    private function buildResponse(): Response
    {
        if ($this->config['use_custom_blocked_page']) {
            return new Response($this->router->generate($this->config['redirect_route']), 403);
        }
        return new RedirectResponse($this->router->generate($this->config['redirect_route']));
    }
}
