<?php

namespace Willypelz\LogPlatform\Strategies;

use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;

class CustomPatternNamingStrategy implements NamingStrategyInterface
{
    protected string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function resolveFilename(\DateTimeInterface $date, string $channel): string
    {
        $replacements = [
            '{channel}' => $channel,
            '{Y}' => $date->format('Y'),
            '{m}' => $date->format('m'),
            '{d}' => $date->format('d'),
            '{H}' => $date->format('H'),
            '{i}' => $date->format('i'),
            '{W}' => $date->format('W'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $this->pattern);
    }

    public function shouldRotate(\DateTimeInterface $lastWrite, \DateTimeInterface $currentWrite): bool
    {
        return $this->resolveFilename($lastWrite, 'test') !==
               $this->resolveFilename($currentWrite, 'test');
    }
}

