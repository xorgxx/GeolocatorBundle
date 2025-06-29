<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Sends JSON notifications to a list of configured webhooks.
 */
final class WebhookNotifier
{
    private HttpClientInterface $httpClient;
    /** @var string[] */
    private array $webhookUrls;
    private LoggerInterface $logger;

    /**
     * @Param httpclientinterface $httpclient client http to send requests.
     * @Param String [] $webhookurls Urls de Webhooks.
     * @Param Loggerinterface $Logger PSR-3 to trace success/errors.
     *
     * @throws invalidargumentexception if an url is invalid.
     */
    public function __construct(
        HttpClientInterface $httpClient,
        array $webhookUrls,
        LoggerInterface $logger
    ) {
        foreach ($webhookUrls as $url) {
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException(sprintf('Invalid webhook URL: %s', is_scalar($url) ? (string)$url : gettype($url)
                ));
            }
        }

        $this->httpClient  = $httpClient;
        $this->webhookUrls = $webhookUrls;
        $this->logger      = $logger;
    }

    /**
     * Send the Payload JSON to each webhooks.
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
                    $this->logger->error('Webhook returned an error code', [
                        'url'        => $url,
                        'statusCode' => $statusCode,
                        'content'    => $response->getContent(false),
                    ]);
                } else {
                    $this->logger->info('Webhook successfully notified', [
                        'url'        => $url,
                        'statusCode' => $statusCode,
                    ]);
                }
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('Failure to send the webhook', [
                    'url'       => $url,
                    'exception' => $e,
                ]);
            }
        }
    }
}
