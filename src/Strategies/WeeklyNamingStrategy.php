<?php

namespace Willypelz\LogPlatform\Strategies;

use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;

class WeeklyNamingStrategy implements NamingStrategyInterface
{
    public function resolveFilename(\DateTimeInterface $date, string $channel): string
    {
        return sprintf(
            '%s-%s-week-%s.log',
            $channel,
            $date->format('Y'),
            $date->format('W')
        );
    }

    public function shouldRotate(\DateTimeInterface $lastWrite, \DateTimeInterface $currentWrite): bool
    {
        return $lastWrite->format('Y-W') !== $currentWrite->format('Y-W');
    }
}

