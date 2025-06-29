<?php
declare(strict_types=1);

namespace GeolocatorBundle\Service;

/**
 * Detects if a User-Agent corresponds to a bot and, if enabled, triggers a challenge.
 */
final class BotDetector
{
    private array $botPatterns;
    private bool  $challengeMode;

    /**
     * @param string[] $botPatterns   Liste de fragments de User-Agent à détecter comme bots.
     * @param bool     $challengeMode Active le mode challenge (ex. CAPTCHA) pour les bots détectés.
     *
     * @throws \InvalidArgumentException Si un pattern n’est pas une chaîne non vide.
     */
    public function __construct(array $botPatterns = [], bool $challengeMode = false)
    {
        foreach ($botPatterns as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                throw new \InvalidArgumentException('Each botPattern must be a non-empty string.');
            }
        }
        $this->botPatterns   = $botPatterns;
        $this->challengeMode = $challengeMode;
    }

    /**
     * Indique si le User-Agent correspond à un bot connu.
     */
    public function isBot(string $userAgent): bool
    {
        foreach ($this->botPatterns as $pattern) {
            if (false !== stripos($userAgent, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Indique si, en mode challenge, ce User-Agent doit être mis au défi.
     */
    public function shouldChallenge(string $userAgent): bool
    {
        return $this->challengeMode && $this->isBot($userAgent);
    }
}
