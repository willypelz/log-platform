<?php

namespace Willypelz\LogPlatform\Contracts;

interface NamingStrategyInterface
{
    /**
     * Resolve the log filename for a given date and channel.
     *
     * @param \DateTimeInterface $date
     * @param string $channel
     * @return string
     */
    public function resolveFilename(\DateTimeInterface $date, string $channel): string;

    /**
     * Determine if the log file should rotate.
     *
     * @param \DateTimeInterface $lastWrite
     * @param \DateTimeInterface $currentWrite
     * @return bool
     */
    public function shouldRotate(\DateTimeInterface $lastWrite, \DateTimeInterface $currentWrite): bool;
}

