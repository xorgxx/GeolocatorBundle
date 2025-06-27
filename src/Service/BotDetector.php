<?php

namespace GeolocatorBundle\Service;

class BotDetector
{
    private array $botPatterns;
    private bool $challengeMode;

    public function __construct(array $botPatterns = [], bool $challengeMode = false)
    {
        $this->botPatterns = $botPatterns;
        $this->challengeMode = $challengeMode;
    }

    public function isBot(string $userAgent): bool
    {
        foreach ($this->botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    public function shouldChallenge(string $userAgent): bool
    {
        return $this->challengeMode && $this->isBot($userAgent);
    }
}
