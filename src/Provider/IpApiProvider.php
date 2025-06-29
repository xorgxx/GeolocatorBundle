<?php

namespace GeolocatorBundle\Provider;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class IpApiProvider implements GeolocationProviderInterface
{
    private HttpClientInterface $client;
    private string $dsn;
    private LoggerInterface $logger;
    private bool $available = true;
    private int $timeout = 3; // Timeout in seconds
    private int $errorCount = 0;
    private const MAX_ERRORS = 3; // Maximum number of errors before considering the provider as unavailable

    public function __construct(HttpClientInterface $client, string $dsn, ?LoggerInterface $logger = null)
    {
        $this->client = $client;
        $this->dsn = $dsn;
        $this->logger = $logger ?? new NullLogger();
    }

    public function getName(): string
    {
        return 'ipapi';
    }

    public function locate(string $ip): array
    {
        if (!$this->available) {
            throw new \RuntimeException(sprintf('Provider %s is temporarily unavailable', $this->getName()));
        }

        try {
            $url = str_replace('{ip}', $ip, $this->dsn);
            $response = $this->client->request('GET', $url, [
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout + 1
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $this->handleError('HTTP error response ' . $statusCode, $ip);
                if ($statusCode === 403 || $statusCode === 429) {
                    // 403: Access denied, 429: Rate limit
                    throw new \RuntimeException(sprintf('Provider %s has denied access (code %d)', $this->getName(), $statusCode));
                }
                return ['error' => true, 'code' => $statusCode];
            }

            // Reset error counter if request is successful
            $this->errorCount = 0;
            return $response->toArray();

        } catch (TransportExceptionInterface $e) {
            $this->handleError('Transport error: ' . $e->getMessage(), $ip);
            return ['error' => true, 'message' => 'timeout'];
        } catch (HttpExceptionInterface $e) {
            $this->handleError('HTTP error: ' . $e->getMessage(), $ip);
            return ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->handleError('General exception: ' . $e->getMessage(), $ip);
            return ['error' => true, 'message' => 'general_error'];
        }
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    private function handleError(string $message, string $ip): void
    {
        $this->errorCount++;
        $this->logger->warning('Error with provider {provider} for IP {ip}: {message}', [
            'provider' => $this->getName(),
            'ip' => $ip,
            'message' => $message,
            'error_count' => $this->errorCount
        ]);

        if ($this->errorCount >= self::MAX_ERRORS) {
            $this->available = false;
            $this->logger->error('Provider {provider} is marked as unavailable after {count} errors', [
                'provider' => $this->getName(),
                'count' => $this->errorCount
            ]);
        }
    }
}
