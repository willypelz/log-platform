<?php

namespace Willypelz\LogPlatform\Strategies;

use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;

class DailyNamingStrategy implements NamingStrategyInterface
{
    public function resolveFilename(\DateTimeInterface $date, string $channel): string
    {
        return sprintf(
            '%s-%s.log',
            $channel,
            $date->format('Y-m-d')
        );
    }

    public function shouldRotate(\DateTimeInterface $lastWrite, \DateTimeInterface $currentWrite): bool
    {
        return $lastWrite->format('Y-m-d') !== $currentWrite->format('Y-m-d');
    }
}

