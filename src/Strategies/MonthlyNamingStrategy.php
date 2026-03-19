<?php

namespace Willypelz\LogPlatform\Strategies;

use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;

class MonthlyNamingStrategy implements NamingStrategyInterface
{
    public function resolveFilename(\DateTimeInterface $date, string $channel): string
    {
        return sprintf(
            '%s-%s.log',
            $channel,
            $date->format('Y-m')
        );
    }

    public function shouldRotate(\DateTimeInterface $lastWrite, \DateTimeInterface $currentWrite): bool
    {
        return $lastWrite->format('Y-m') !== $currentWrite->format('Y-m');
    }
}

