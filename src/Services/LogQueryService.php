<?php

namespace Willypelz\LogPlatform\Services;

use Willypelz\LogPlatform\Contracts\QueryEngineInterface;
use Willypelz\LogPlatform\Models\IndexedLog;

class LogQueryService implements QueryEngineInterface
{
    protected StructuredQueryParser $queryParser;
    protected ?FileOnlyLogReader $fileReader = null;

    public function __construct(StructuredQueryParser $queryParser)
    {
        $this->queryParser = $queryParser;

        // Initialize file reader if indexing is disabled
        if (!config('log-platform.indexing.enabled', true)) {
            $this->fileReader = app(FileOnlyLogReader::class);
        }
    }

    public function query(string $query, array $filters = [], int $limit = 100, ?string $cursor = null): array
    {
        // File-only mode
        if ($this->fileReader) {
            return $this->queryFromFiles($query, $filters, $limit);
        }

        // Database mode (original implementation)
        // Parse structured query
        $parsedQuery = $this->queryParser->parse($query);

        // Build Eloquent query
        $builder = IndexedLog::query();

        // Apply structured query filters
        if (!empty($parsedQuery['level'])) {
            $builder->whereIn('level', $parsedQuery['level']);
        }

        foreach ($parsedQuery['message_contains'] as $keyword) {
            $builder->where('message', 'like', '%' . $keyword . '%');
        }

        foreach ($parsedQuery['context'] as $key => $value) {
            $builder->whereJsonContains('context->' . $key, $value);
        }

        foreach ($parsedQuery['not'] as $field => $value) {
            if ($field === 'level') {
                $builder->where('level', '!=', $value);
            }
        }

        // Apply additional filters
        if (isset($filters['env'])) {
            $builder->environment($filters['env']);
        }

        if (isset($filters['level'])) {
            $builder->level($filters['level']);
        }

        if (isset($filters['from']) && isset($filters['to'])) {
            $builder->dateRange($filters['from'], $filters['to']);
        }

        if (isset($filters['keyword'])) {
            $builder->keyword($filters['keyword']);
        }

        if (isset($filters['request_id'])) {
            $builder->requestId($filters['request_id']);
        }

        if (isset($filters['fingerprint'])) {
            $builder->fingerprint($filters['fingerprint']);
        }

        // Apply cursor pagination
        if ($cursor) {
            $builder->where('id', '<', $cursor);
        }

        // Order by latest first
        $builder->orderBy('logged_at', 'desc')->orderBy('id', 'desc');

        // Fetch with one extra to determine hasMore
        $results = $builder->limit($limit + 1)->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results->pop();
        }

        $nextCursor = $hasMore && $results->isNotEmpty()
            ? $results->last()->id
            : null;

        return [
            'data' => $results->toArray(),
            'cursor' => $nextCursor,
            'hasMore' => $hasMore,
        ];
    }

    public function getByRequestId(string $requestId): array
    {
        // File-only mode
        if ($this->fileReader) {
            $logPath = storage_path('logs');
            $files = glob($logPath . '/*.log');
            $allLogs = [];

            foreach ($files as $file) {
                $logs = $this->fileReader->readFile($file, [], 500);
                $allLogs = array_merge($allLogs, $logs);
            }

            // Filter by request_id
            return array_filter($allLogs, function($log) use ($requestId) {
                return isset($log['request_id']) && $log['request_id'] === $requestId;
            });
        }

        return IndexedLog::requestId($requestId)
            ->orderBy('logged_at', 'asc')
            ->get()
            ->toArray();
    }

    public function getByFingerprint(string $fingerprint, int $limit = 100): array
    {
        // File-only mode
        if ($this->fileReader) {
            $logPath = storage_path('logs');
            $files = glob($logPath . '/*.log');
            $allLogs = [];

            foreach ($files as $file) {
                $logs = $this->fileReader->readFile($file, [], 500);
                $allLogs = array_merge($allLogs, $logs);
            }

            // Filter by fingerprint and limit
            $filtered = array_filter($allLogs, function($log) use ($fingerprint) {
                return isset($log['fingerprint']) && $log['fingerprint'] === $fingerprint;
            });

            return array_slice($filtered, 0, $limit);
        }

        return IndexedLog::fingerprint($fingerprint)
            ->orderBy('logged_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function queryFromFiles(string $query, array $filters, int $limit): array
    {
        $logPath = storage_path('logs');
        $files = glob($logPath . '/*.log');

        // Sort files by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $logs = $this->fileReader->searchFiles($files, $filters, $limit);

        return [
            'data' => $logs,
            'cursor' => null,
            'hasMore' => false,
            'mode' => 'file-only',
        ];
    }
}

