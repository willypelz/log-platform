<?php

namespace Willypelz\LogPlatform\Services;

use Willypelz\LogPlatform\Contracts\IndexerStoreInterface;
use Willypelz\LogPlatform\Models\IndexedLog;
use Willypelz\LogPlatform\Models\LogFileState;

class DatabaseIndexerStore implements IndexerStoreInterface
{
    public function storeBulk(array $entries): int
    {
        if (empty($entries)) {
            return 0;
        }

        // Use insert to avoid overhead of Eloquent model creation
        IndexedLog::insert($entries);

        return count($entries);
    }

    public function getFileState(string $filePath, string $env, string $channel): array
    {
        $state = LogFileState::getOrCreate($filePath, $env, $channel);

        return [
            'offset' => $state->last_offset,
            'inode' => $state->inode,
            'hash' => $state->last_hash,
        ];
    }

    public function updateFileState(
        string $filePath,
        string $env,
        string $channel,
        int $offset,
        ?int $inode = null,
        ?string $hash = null
    ): void {
        LogFileState::updateOrCreate(
            [
                'path' => $filePath,
                'env' => $env,
                'channel' => $channel,
            ],
            [
                'last_offset' => $offset,
                'inode' => $inode,
                'last_hash' => $hash,
                'last_seen_at' => now(),
                'status' => 'active',
            ]
        );
    }
}

