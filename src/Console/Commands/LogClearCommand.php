<?php

namespace Willypelz\LogPlatform\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogClearCommand extends Command
{
    protected $signature = 'log:clear
                            {--days=30 : Delete log files older than this many days}
                            {--force : Skip confirmation}';

    protected $description = 'Delete old physical log files from storage/logs';

    public function handle(): int
    {
        if (!config('log-platform.retention.enabled', false)) {
            $this->warn('Retention is disabled. Enable it in config/log-platform.php (retention.enabled = true).');
            return self::SUCCESS;
        }

        $days   = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $path   = storage_path('logs');
        $files  = File::glob("{$path}/*.log");

        $toDelete = array_filter($files, fn($f) => File::lastModified($f) < $cutoff->timestamp);

        if (empty($toDelete)) {
            $this->info("No log files older than {$days} days found.");
            return self::SUCCESS;
        }

        $this->warn('Files to delete (' . count($toDelete) . '):');
        foreach ($toDelete as $file) {
            $this->line('  ' . basename($file));
        }

        if (!$this->option('force') && !$this->confirm('Proceed with deletion?')) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        foreach ($toDelete as $file) {
            File::delete($file);
        }

        $this->info('✅ Deleted ' . count($toDelete) . ' log file(s).');
        return self::SUCCESS;
    }
}

