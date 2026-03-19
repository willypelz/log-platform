<?php

namespace Willypelz\LogPlatform\Services;

use Willypelz\LogPlatform\Contracts\LogParserInterface;

class LogParser implements LogParserInterface
{
    /**
     * Laravel log format regex pattern.
     */
    protected const PATTERN = '/^\[(\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2})?)\]\s+(\w+)\.(\w+):\s+(.+)$/s';

    protected array $multilineBuffer = [];
    protected ?array $currentEntry = null;

    public function parse(string $line, array $context = []): array
    {
        // Check if this is a new log entry
        if ($this->isLogStart($line)) {
            // Flush previous multiline entry if exists
            $previousEntry = $this->flushBuffer();

            // Parse new entry
            if (preg_match(self::PATTERN, $line, $matches)) {
                $this->currentEntry = [
                    'logged_at' => $this->parseTimestamp($matches[1]),
                    'env' => $matches[2] ?? 'local',
                    'level' => strtolower($matches[3]),
                    'message' => trim($matches[4]),
                    'context' => [],
                    'request_id' => null,
                    'fingerprint' => null,
                    'source_file' => $context['file'] ?? null,
                    'source_offset' => $context['offset'] ?? 0,
                ];

                // Extract JSON context if present
                $this->extractContext($this->currentEntry);

                // Start multiline buffer
                $this->multilineBuffer = [$line];

                return [
                    'success' => true,
                    'data' => $previousEntry,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'data' => $previousEntry,
                'error' => 'Failed to match log pattern',
            ];
        }

        // Append to multiline buffer
        if ($this->currentEntry) {
            $this->multilineBuffer[] = $line;
            $this->currentEntry['message'] .= "\n" . $line;
        }

        return [
            'success' => true,
            'data' => null,
            'error' => null,
        ];
    }

    public function isLogStart(string $line): bool
    {
        return preg_match('/^\[\d{4}-\d{2}-\d{2}/', $line) === 1;
    }

    /**
     * Flush the multiline buffer and return complete entry.
     */
    public function flushBuffer(): ?array
    {
        if (!$this->currentEntry) {
            return null;
        }

        $entry = $this->currentEntry;
        $this->currentEntry = null;
        $this->multilineBuffer = [];

        return $entry;
    }

    /**
     * Parse timestamp string to datetime.
     */
    protected function parseTimestamp(string $timestamp): string
    {
        try {
            $dt = new \DateTimeImmutable($timestamp);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return date('Y-m-d H:i:s');
        }
    }

    /**
     * Extract JSON context from message.
     */
    protected function extractContext(array &$entry): void
    {
        // Look for JSON context at the end of the message
        if (preg_match('/\s+(\{.+\})\s*$/s', $entry['message'], $matches)) {
            try {
                $context = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);

                if (is_array($context)) {
                    $entry['context'] = $context;

                    // Extract request_id if present
                    if (isset($context['request_id'])) {
                        $entry['request_id'] = $context['request_id'];
                    }

                    // Extract fingerprint if present
                    if (isset($context['fingerprint'])) {
                        $entry['fingerprint'] = $context['fingerprint'];
                    }

                    // Clean message
                    $entry['message'] = trim(str_replace($matches[1], '', $entry['message']));
                }
            } catch (\JsonException $e) {
                // Not valid JSON, leave as is
            }
        }
    }
}

