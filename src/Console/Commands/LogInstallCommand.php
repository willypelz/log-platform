<?php

namespace Willypelz\LogPlatform\Console\Commands;

use Illuminate\Console\Command;

class LogInstallCommand extends Command
{
    protected $signature = 'log:install';
    protected $description = 'Install the Log Platform package (file-only log browser)';

    public function handle(): int
    {
        $this->info('Installing Log Platform...');
        $this->newLine();

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', ['--tag' => 'log-platform-config']);

        $this->newLine();
        $this->info('✅ Log Platform installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Review config/log-platform.php');
        $this->line('  2. Visit /log-platform to browse your storage/logs files');
        $this->newLine();
        $this->line('No database migrations are required — this package is file-only.');

        return self::SUCCESS;
    }
}

