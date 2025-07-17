<?php

namespace GeolocatorBundle\Service;

use GeolocatorBundle\Model\GeoLocation;
use Symfony\Component\HttpFoundation\Request;

class CrawlerFilter
{
    private array $config;
    private array $knownCrawlers = [
        'googlebot' => 'Google',
        'bingbot' => 'Bing',
        'yandexbot' => 'Yandex',
        'baiduspider' => 'Baidu',
        'duckduckbot' => 'DuckDuckGo',
        'slurp' => 'Yahoo',
        'applebot' => 'Apple',
        'facebookexternalhit' => 'Facebook',
        'twitterbot' => 'Twitter',
        'rogerbot' => 'Moz',
        'linkedinbot' => 'LinkedIn',
        'embedly' => 'Embedly',
        'pinterestbot' => 'Pinterest',
        'slackbot' => 'Slack',
        'discordbot' => 'Discord',
        'telegrambot' => 'Telegram',
        'archive.org_bot' => 'Internet Archive',
        'ahrefsbot' => 'Ahrefs',
        'semrushbot' => 'SEMrush',
        'screaming frog' => 'Screaming Frog',
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Détecte si une requête provient d'un crawler et retourne des détails sur le type de détection.
     *
     * @param Request $request La requête HTTP
     * @param GeoLocation|null $geoLocation Informations de géolocalisation optionnelles
     * @return array Tableau avec les clés 'isCrawler', 'isKnown', 'name' et 'reason'
     */
    public function detectCrawler(Request $request, ?GeoLocation $geoLocation = null): array
    {
        $result = [
            'isCrawler' => false,
            'isKnown' => false,
            'name' => null,
            'reason' => null
        ];

        if (!isset($this->config['enabled']) || !$this->config['enabled']) {
            return $result;
        }

        $userAgent = $request->headers->get('User-Agent', '');

        if (empty($userAgent)) {
            // User-Agent vide est suspect
            $result['isCrawler'] = true;
            $result['reason'] = 'Empty User-Agent';
            return $result;
        }

        $userAgentLower = strtolower($userAgent);

        // Vérifier si c'est un crawler connu
        foreach ($this->knownCrawlers as $crawlerKeyword => $crawlerName) {
            if (strpos($userAgentLower, $crawlerKeyword) !== false) {
                // C'est un crawler connu
                $result['isCrawler'] = true;
                $result['isKnown'] = true;
                $result['name'] = $crawlerName;
                $result['reason'] = 'Known crawler: ' . $crawlerName;
                return $result;
            }
        }

        // Patterns communs de crawlers
        $botPatterns = [
            '/bot\b/i' => 'Bot pattern',
            '/crawler\b/i' => 'Crawler pattern',
            '/spider\b/i' => 'Spider pattern',
            '/spyder\b/i' => 'Spyder pattern',
            '/crawl\b/i' => 'Crawl pattern',
            '/slurp\b/i' => 'Slurp pattern',
            '/scraper\b/i' => 'Scraper pattern',
            '/fetcher\b/i' => 'Fetcher pattern',
            '/archiver\b/i' => 'Archiver pattern',
            '/sitesucker\b/i' => 'Site sucker pattern',
            '/nutch\b/i' => 'Nutch pattern',
            '/capture\b/i' => 'Capture pattern',
            '/index\b/i' => 'Index pattern',
            '/monitor\b/i' => 'Monitor pattern',
            '/analyze\b/i' => 'Analyze pattern',
            '/scoop\b/i' => 'Scoop pattern',
            '/scan\b/i' => 'Scan pattern',
            '/check\b/i' => 'Check pattern',
            '/search\b/i' => 'Search pattern',
            '/Wget\b/i' => 'Wget pattern',
            '/cURL\b/i' => 'cURL pattern',
        ];

        foreach ($botPatterns as $pattern => $patternName) {
            if (preg_match($pattern, $userAgent)) {
                $result['isCrawler'] = true;
                $result['reason'] = $patternName;
                return $result;
            }
        }

        return $result;
    }

    /**
     * Vérifie si une requête provient d'un crawler.
     */
    public function isCrawler(Request $request, ?GeoLocation $geoLocation = null): bool
    {
        if (!isset($this->config['enabled']) || !$this->config['enabled']) {
            return false;
        }

        $userAgent = $request->headers->get('User-Agent', '');

        if (empty($userAgent)) {
            // User-Agent vide est suspect
            return true;
        }

        $userAgentLower = strtolower($userAgent);

        // Vérifier si c'est un crawler connu
        foreach ($this->knownCrawlers as $crawlerKeyword => $crawlerName) {
            if (strpos($userAgentLower, $crawlerKeyword) !== false) {
                // C'est un crawler connu, vérifier si on les autorise
                return !isset($this->config['allow_known']) ? true : !$this->config['allow_known'];
            }
        }

        // Patterns communs de crawlers
        $botPatterns = [
            '/bot\b/i',
            '/crawler\b/i',
            '/spider\b/i',
            '/spyder\b/i',
            '/crawl\b/i',
            '/slurp\b/i',
            '/scraper\b/i',
            '/fetcher\b/i',
            '/archiver\b/i',
            '/sitesucker\b/i',
            '/nutch\b/i',
            '/capture\b/i',
            '/index\b/i',
            '/monitor\b/i',
            '/analyze\b/i',
            '/scoop\b/i',
            '/scan\b/i',
            '/check\b/i',
            '/search\b/i',
            '/Wget\b/i',
            '/cURL\b/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }
}
