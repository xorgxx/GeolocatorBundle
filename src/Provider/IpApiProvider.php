<?php

namespace GeolocatorBundle\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class IpApiProvider implements GeolocationProviderInterface
{
    private HttpClientInterface $client;
    private string $dsn;

    public function __construct(HttpClientInterface $client, string $dsn)
    {
        $this->client = $client;
        $this->dsn = $dsn;
    }

    public function getName(): string
    {
        return 'ipapi';
    }

    public function locate(string $ip): array
    {
        $url = str_replace('{ip}', $ip, $this->dsn);
        $response = $this->client->request('GET', $url);
        return $response->toArray();
    }
}
