<?php

namespace Willypelz\LogPlatform\Console\Commands;

use Willypelz\LogPlatform\Models\IndexedLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogClearCommand extends Command
{
    protected $signature = 'log:clear
                            {--days=30 : Clear logs older than this many days}
                            {--level= : Clear specific log level}
                            {--env= : Clear specific environment}
                            {--files : Also delete physical log files}
                            {--force : Skip confirmation}';

    protected $description = 'Clear old log entries';

    public function handle(): int
    {
        $days = $this->option('days');
        $level = $this->option('level');
        $env = $this->option('env');
        $deleteFiles = $this->option('files');
        $force = $this->option('force');

        $cutoff = now()->subDays($days);

        $query = IndexedLog::where('logged_at', '<', $cutoff);

        if ($level) {
            $query->level($level);
        }

        if ($env) {
            $query->environment($env);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No logs to clear.');
            return self::SUCCESS;
        }

        $this->warn("Found {$count} log entries to delete (older than {$days} days)");

        if (!$force && !$this->confirm('Continue?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        // Delete from database
        $deleted = $query->delete();
        $this->info("✅ Deleted {$deleted} log entries from database");

        // Delete physical files if requested
        if ($deleteFiles) {
            $this->deleteOldFiles($days);
        }

        return self::SUCCESS;
    }

    protected function deleteOldFiles(int $days): void
    {
        $logPath = storage_path('logs');
        $cutoff = now()->subDays($days);

        $files = File::glob("{$logPath}/*.log");
        $deletedCount = 0;

        foreach ($files as $file) {
            $mtime = File::lastModified($file);

            if ($mtime < $cutoff->timestamp) {
                File::delete($file);
                $deletedCount++;
                $this->line("  Deleted: " . basename($file));
            }
        }

        if ($deletedCount > 0) {
            $this->info("✅ Deleted {$deletedCount} log file(s)");
        }
    }
}

