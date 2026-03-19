<?php

namespace Willypelz\LogPlatform\Console\Commands;

use Willypelz\LogPlatform\Jobs\IndexLogChunkJob;
use Willypelz\LogPlatform\Services\LogIndexer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogIndexCommand extends Command
{
    protected $signature = 'log:index
                            {--env= : Environment to index}
                            {--channel=laravel : Channel to index}
                            {--from= : Index from date (Y-m-d)}
                            {--rebuild : Rebuild index from scratch}
                            {--async : Run indexing in background queue}';

    protected $description = 'Index log files for fast searching';

    public function handle(LogIndexer $indexer): int
    {
        $env = $this->option('env') ?? config('app.env');
        $channel = $this->option('channel');
        $from = $this->option('from');
        $rebuild = $this->option('rebuild');
        $async = $this->option('async');

        $logPath = storage_path('logs');

        if (!is_dir($logPath)) {
            $this->error("Log directory not found: {$logPath}");
            return self::FAILURE;
        }

        // Find log files
        $pattern = $from
            ? "{$channel}-{$from}*.log"
            : "{$channel}-*.log";

        $files = File::glob("{$logPath}/{$pattern}");

        if (empty($files)) {
            $this->warn("No log files found matching: {$pattern}");
            return self::SUCCESS;
        }

        $this->info("Found " . count($files) . " log file(s)");

        $totalIndexed = 0;

        foreach ($files as $file) {
            $filename = basename($file);
            $this->line("Indexing: {$filename}");

            if ($async) {
                IndexLogChunkJob::dispatch($file, $env, $channel);
                $this->info("  → Queued for background processing");
            } else {
                $result = $rebuild
                    ? $indexer->rebuild($file, $env, $channel)
                    : $indexer->indexFile($file, $env, $channel);

                if ($result['success']) {
                    $totalIndexed += $result['indexed'];
                    $this->info("  → Indexed {$result['indexed']} entries");
                } else {
                    $this->error("  → Error: {$result['error']}");
                }
            }
        }

        if (!$async) {
            $this->newLine();
            $this->info("✅ Total indexed: {$totalIndexed} log entries");
        } else {
            $this->newLine();
            $this->info("✅ Queued " . count($files) . " file(s) for indexing");
        }

        return self::SUCCESS;
    }
}

