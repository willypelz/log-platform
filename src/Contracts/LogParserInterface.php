<?php

namespace Willypelz\LogPlatform\Contracts;

interface LogParserInterface
{
    /**
     * Parse a log line into structured data.
     *
     * @param string $line
     * @param array $context Additional context (file, offset, etc.)
     * @return array{success: bool, data: ?array, error: ?string}
     */
    public function parse(string $line, array $context = []): array;

    /**
     * Check if a line is the start of a new log entry.
     *
     * @param string $line
     * @return bool
     */
    public function isLogStart(string $line): bool;
}

