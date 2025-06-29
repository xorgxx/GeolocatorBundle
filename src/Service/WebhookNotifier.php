<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Envoie des notifications JSON à une liste de webhooks configurés.
 */
final class WebhookNotifier
{
    private HttpClientInterface $httpClient;
    /** @var string[] */
    private array $webhookUrls;
    private LoggerInterface $logger;

    /**
     * @param HttpClientInterface $httpClient  Client HTTP pour envoyer les requêtes.
     * @param string[]            $webhookUrls Liste d’URLs de webhooks.
     * @param LoggerInterface     $logger      Logger PSR-3 pour tracer succès/erreurs.
     *
     * @throws \InvalidArgumentException Si une URL est invalide.
     */
    public function __construct(
        HttpClientInterface $httpClient,
        array $webhookUrls,
        LoggerInterface $logger
    ) {
        foreach ($webhookUrls as $url) {
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException(sprintf(
                    'URL de webhook invalide : %s',
                    is_scalar($url) ? (string)$url : gettype($url)
                ));
            }
        }

        $this->httpClient  = $httpClient;
        $this->webhookUrls = $webhookUrls;
        $this->logger      = $logger;
    }

    /**
     * Envoie le payload JSON à chacun des webhooks.
     *
     * @param string $payload Chaîne JSON à transmettre en POST.
     */
    public function notify(string $payload): void
    {
        foreach ($this->webhookUrls as $url) {
            try {
                $response = $this->httpClient->request('POST', $url, [
                    'body'    => $payload,
                    'headers' => ['Content-Type' => 'application/json'],
                    'timeout' => 5.0,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode >= 400) {
                    $this->logger->error('Webhook retourné un code d’erreur', [
                        'url'        => $url,
                        'statusCode' => $statusCode,
                        'content'    => $response->getContent(false),
                    ]);
                } else {
                    $this->logger->info('Webhook notifié avec succès', [
                        'url'        => $url,
                        'statusCode' => $statusCode,
                    ]);
                }
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Échec de l’envoi du webhook', [
                    'url'       => $url,
                    'exception' => $e,
                ]);
            }
        }
    }
}
