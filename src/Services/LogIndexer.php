<?php

namespace Willypelz\LogPlatform\Services;

use Willypelz\LogPlatform\Contracts\LogParserInterface;
use Willypelz\LogPlatform\Contracts\IndexerStoreInterface;
use Willypelz\LogPlatform\Models\LogFileState;

class LogIndexer
{
    protected LogParserInterface $parser;
    protected IndexerStoreInterface $store;
    protected int $chunkSize;
    protected int $batchSize;

    public function __construct(
        LogParserInterface $parser,
        IndexerStoreInterface $store,
        int $chunkSize = 65536,
        int $batchSize = 1000
    ) {
        $this->parser = $parser;
        $this->store = $store;
        $this->chunkSize = $chunkSize;
        $this->batchSize = $batchSize;
    }

    /**
     * Index a log file incrementally.
     */
    public function indexFile(string $filePath, string $env, string $channel): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File not found',
                'indexed' => 0,
            ];
        }

        // Get file state
        $state = $this->store->getFileState($filePath, $env, $channel);
        $startOffset = $state['offset'];

        // Open file
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'success' => false,
                'error' => 'Failed to open file',
                'indexed' => 0,
            ];
        }

        // Get file inode
        $stats = fstat($handle);
        $inode = $stats['ino'] ?? null;

        // Check if file was rotated (inode changed)
        if ($state['inode'] && $state['inode'] !== $inode) {
            // File was rotated, start from beginning
            $startOffset = 0;
        }

        // Seek to last position
        fseek($handle, $startOffset);

        $batch = [];
        $indexed = 0;
        $currentOffset = $startOffset;
        $partialLine = '';

        while (!feof($handle)) {
            // Read chunk
            $chunk = fread($handle, $this->chunkSize);
            if ($chunk === false) {
                break;
            }

            $currentOffset = ftell($handle);

            // Combine with partial line from previous chunk
            $chunk = $partialLine . $chunk;

            // Split into lines
            $lines = explode("\n", $chunk);

            // Last line might be partial
            $partialLine = array_pop($lines);

            // Process complete lines
            foreach ($lines as $line) {
                $result = $this->parser->parse($line, [
                    'file' => $filePath,
                    'offset' => $currentOffset,
                ]);

                if ($result['data']) {
                    $batch[] = $result['data'];

                    if (count($batch) >= $this->batchSize) {
                        $indexed += $this->store->storeBulk($batch);
                        $batch = [];
                    }
                }
            }
        }

        // Handle remaining partial line
        if ($partialLine) {
            $result = $this->parser->parse($partialLine, [
                'file' => $filePath,
                'offset' => $currentOffset,
            ]);

            if ($result['data']) {
                $batch[] = $result['data'];
            }
        }

        // Flush final buffer entry
        $finalEntry = $this->parser->flushBuffer();
        if ($finalEntry) {
            $batch[] = $finalEntry;
        }

        // Store remaining batch
        if (!empty($batch)) {
            $indexed += $this->store->storeBulk($batch);
        }

        fclose($handle);

        // Update file state
        $this->store->updateFileState(
            $filePath,
            $env,
            $channel,
            $currentOffset,
            $inode,
            md5_file($filePath)
        );

        return [
            'success' => true,
            'error' => null,
            'indexed' => $indexed,
            'offset' => $currentOffset,
        ];
    }

    /**
     * Index multiple files.
     */
    public function indexFiles(array $files, string $env, string $channel): array
    {
        $results = [];
        $totalIndexed = 0;

        foreach ($files as $file) {
            $result = $this->indexFile($file, $env, $channel);
            $results[] = $result;
            $totalIndexed += $result['indexed'] ?? 0;
        }

        return [
            'total_indexed' => $totalIndexed,
            'files' => $results,
        ];
    }

    /**
     * Rebuild index from scratch.
     */
    public function rebuild(string $filePath, string $env, string $channel): array
    {
        // Reset file state
        LogFileState::where('path', $filePath)
            ->where('env', $env)
            ->where('channel', $channel)
            ->update(['last_offset' => 0]);

        return $this->indexFile($filePath, $env, $channel);
    }
}

