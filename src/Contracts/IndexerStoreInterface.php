<?php

namespace Willypelz\LogPlatform\Contracts;

interface IndexerStoreInterface
{
    /**
     * Store parsed log entries in bulk.
     *
     * @param array $entries
     * @return int Number of entries stored
     */
    public function storeBulk(array $entries): int;

    /**
     * Get the last indexed offset for a file.
     *
     * @param string $filePath
     * @param string $env
     * @param string $channel
     * @return array{offset: int, inode: ?int, hash: ?string}
     */
    public function getFileState(string $filePath, string $env, string $channel): array;

    /**
     * Update file indexing state.
     *
     * @param string $filePath
     * @param string $env
     * @param string $channel
     * @param int $offset
     * @param int|null $inode
     * @param string|null $hash
     * @return void
     */
    public function updateFileState(
        string $filePath,
        string $env,
        string $channel,
        int $offset,
        ?int $inode = null,
        ?string $hash = null
    ): void;
}

