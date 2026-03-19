<?php

namespace Willypelz\LogPlatform\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class FingerprintProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['fingerprint'] = $this->generateFingerprint($record);

        return $record;
    }

    /**
     * Generate a fingerprint for log grouping.
     */
    protected function generateFingerprint(LogRecord $record): string
    {
        // Normalize message by removing dynamic parts
        $normalized = $this->normalizeMessage($record->message);

        // Include level and channel for better grouping
        $input = sprintf(
            '%s:%s:%s',
            $record->level->getName(),
            $record->channel,
            $normalized
        );

        return hash('xxh3', $input);
    }

    /**
     * Normalize message by removing dynamic content.
     */
    protected function normalizeMessage(string $message): string
    {
        // Replace numbers
        $message = preg_replace('/\b\d+\b/', '{num}', $message);

        // Replace UUIDs
        $message = preg_replace(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
            '{uuid}',
            $message
        );

        // Replace file paths
        $message = preg_replace('#(/[\w\-./]+)+#', '{path}', $message);

        // Replace timestamps
        $message = preg_replace('/\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', '{timestamp}', $message);

        // Replace IP addresses
        $message = preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', '{ip}', $message);

        return $message;
    }
}

