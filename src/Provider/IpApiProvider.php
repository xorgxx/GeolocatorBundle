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
    private int $timeout = 3; // Timeout en secondes
    private int $errorCount = 0;
    private const MAX_ERRORS = 3; // Nombre d'erreurs tolérées avant de considérer le provider comme non disponible

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
            throw new \RuntimeException(sprintf('Le fournisseur %s est temporairement indisponible', $this->getName()));
        }

        try {
            $url = str_replace('{ip}', $ip, $this->dsn);
            $response = $this->client->request('GET', $url, [
                'timeout' => $this->timeout,
                'max_duration' => $this->timeout + 1
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $this->handleError('Réponse HTTP erreur ' . $statusCode, $ip);
                if ($statusCode === 403 || $statusCode === 429) {
                    // 403: Accès refusé, 429: Rate limit
                    throw new \RuntimeException(sprintf('Le fournisseur %s a refusé l\'accès (code %d)', $this->getName(), $statusCode));
                }
                return ['error' => true, 'code' => $statusCode];
            }

            // Réinitialiser le compteur d'erreurs si la requête est réussie
            $this->errorCount = 0;
            return $response->toArray();

        } catch (TransportExceptionInterface $e) {
            $this->handleError('Erreur de transport: ' . $e->getMessage(), $ip);
            return ['error' => true, 'message' => 'timeout'];
        } catch (HttpExceptionInterface $e) {
            $this->handleError('Erreur HTTP: ' . $e->getMessage(), $ip);
            return ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->handleError('Exception générale: ' . $e->getMessage(), $ip);
            return ['error' => true, 'message' => 'erreur_generale'];
        }
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    private function handleError(string $message, string $ip): void
    {
        $this->errorCount++;
        $this->logger->warning('Erreur avec le fournisseur {provider} pour IP {ip}: {message}', [
            'provider' => $this->getName(),
            'ip' => $ip,
            'message' => $message,
            'error_count' => $this->errorCount
        ]);

        if ($this->errorCount >= self::MAX_ERRORS) {
            $this->available = false;
            $this->logger->error('Le fournisseur {provider} est marqué comme indisponible après {count} erreurs', [
                'provider' => $this->getName(),
                'count' => $this->errorCount
            ]);
        }
    }
}
