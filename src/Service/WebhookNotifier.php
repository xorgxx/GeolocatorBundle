<?php

namespace GeolocatorBundle\Service;

class WebhookNotifier
{
    private array $webhooks;

    public function __construct(array $webhooks = [])
    {
        $this->webhooks = $webhooks;
    }

    public function notify(string $payload): void
    {
        foreach ($this->webhooks as $url) {
            // fire webhook (async or curl)
        }
    }
}
