<?php

namespace GeolocatorBundle\Provider;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class IpApiProvider implements GeolocationProviderInterface
{
    private const MAX_ERRORS = 3;

    private HttpClientInterface $client;
    private string $dsn;
    private LoggerInterface $logger;
    private bool $available = true;
    private int $errorCount = 0;
    private int $timeout;

    public function __construct(
        HttpClientInterface $client,
        string $dsn,
        int $timeout = 5,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->dsn = $dsn;
        $this->timeout = $timeout;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getName(): string
    {
        return 'ipapi';
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function locate(string $ip): array
    {
        $url = str_replace('{ip}', $ip, $this->dsn);
        try {
            $response = $this->client->request('GET', $url, [
                'timeout' => $this->timeout,
            ]);
            $data = $response->toArray();
            $this->errorCount = 0;
            return $data;
        } catch (\Exception $e) {
            $this->handleError($e->getMessage());
            return [];
        }
    }

    private function handleError(string $message): void
    {
        $this->errorCount++;
        $this->logger->warning(sprintf(
            '[%s] Error #%d: %s',
            $this->getName(),
            $this->errorCount,
            $message
        ));

        if ($this->errorCount >= self::MAX_ERRORS) {
            $this->available = false;
            $this->logger->error(
                sprintf('[%s] Marked unavailable after %d errors', $this->getName(), $this->errorCount)
            );
        }
    }
}