<?php

namespace Willypelz\LogPlatform\Services;

use Willypelz\LogPlatform\Contracts\LogParserInterface;
use Illuminate\Support\Facades\File;

/**
 * File-only log reader for when database indexing is disabled.
 * Reads and parses log files directly without database storage.
 */
class FileOnlyLogReader
{
    protected LogParserInterface $parser;
    protected int $maxLinesPerQuery = 500;

    public function __construct(LogParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Read and parse logs from file with filters.
     */
    public function readFile(string $filePath, array $filters = [], int $limit = 100): array
    {
        if (!File::exists($filePath)) {
            return [];
        }

        $logs = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            return [];
        }

        $lineNumber = 0;
        $currentEntry = null;

        // For reverse reading (latest first), we'd need to read backwards
        // For simplicity in file-only mode, we'll read forward and take last N
        while (!feof($handle) && count($logs) < $this->maxLinesPerQuery) {
            $line = fgets($handle);
            $lineNumber++;

            if ($line === false) {
                break;
            }

            $result = $this->parser->parse(trim($line), [
                'file' => $filePath,
                'line' => $lineNumber,
            ]);

            if ($result['data']) {
                $entry = $result['data'];

                // Apply filters
                if ($this->matchesFilters($entry, $filters)) {
                    $logs[] = $entry;
                }
            }
        }

        // Get last entry
        $finalEntry = $this->parser->flushBuffer();
        if ($finalEntry && $this->matchesFilters($finalEntry, $filters)) {
            $logs[] = $finalEntry;
        }

        fclose($handle);

        // Take last N entries (most recent)
        $logs = array_slice($logs, -$limit);

        // Reverse to show newest first
        return array_reverse($logs);
    }

    /**
     * Check if entry matches filters.
     */
    protected function matchesFilters(array $entry, array $filters): bool
    {
        // Level filter
        if (!empty($filters['level']) && $entry['level'] !== $filters['level']) {
            return false;
        }

        // Keyword filter
        if (!empty($filters['keyword'])) {
            if (stripos($entry['message'], $filters['keyword']) === false) {
                return false;
            }
        }

        // Date range filter
        if (!empty($filters['from'])) {
            if ($entry['logged_at'] < $filters['from']) {
                return false;
            }
        }

        if (!empty($filters['to'])) {
            if ($entry['logged_at'] > $filters['to']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Search across multiple files.
     */
    public function searchFiles(array $files, array $filters = [], int $limit = 100): array
    {
        $allLogs = [];

        foreach ($files as $file) {
            $logs = $this->readFile($file, $filters, $limit);
            $allLogs = array_merge($allLogs, $logs);
        }

        // Sort by date descending
        usort($allLogs, function($a, $b) {
            return strcmp($b['logged_at'], $a['logged_at']);
        });

        return array_slice($allLogs, 0, $limit);
    }
}

