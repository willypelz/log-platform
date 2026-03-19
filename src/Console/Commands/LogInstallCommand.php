<?php

namespace Willypelz\LogPlatform\Console\Commands;

use Illuminate\Console\Command;

class LogInstallCommand extends Command
{
    protected $signature = 'log:install';
    protected $description = 'Install the Log Platform package';

    public function handle(): int
    {
        $this->info('Installing Log Platform...');
        $this->newLine();

        // Ask about database indexing
        $useDatabase = $this->choice(
            'Do you want to use database indexing? (Recommended for production)',
            ['yes', 'no', 'decide-later'],
            'yes'
        );

        // Publish config
        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'log-platform-config',
        ]);

        if ($useDatabase === 'yes') {
            $this->info('✅ Database indexing enabled');

            // Publish migrations
            $this->info('Publishing migrations...');
            $this->call('vendor:publish', [
                '--tag' => 'log-platform-migrations',
            ]);

            // Run migrations
            if ($this->confirm('Run migrations now?', true)) {
                $this->call('migrate');
            }

            $this->newLine();
            $this->info('📊 Database indexing is enabled');
            $this->line('   You can now use: php artisan log:index');

        } elseif ($useDatabase === 'no') {
            $this->warn('⚠️  Database indexing disabled (file-only mode)');

            // Update config to disable indexing
            $this->disableIndexingInConfig();

            $this->newLine();
            $this->info('📁 File-only mode enabled');
            $this->line('   Features available:');
            $this->line('   ✅ Log viewing from files');
            $this->line('   ✅ Custom rotation strategies');
            $this->line('   ✅ Real-time streaming');
            $this->line('   ✅ File management');
            $this->newLine();
            $this->line('   Features NOT available:');
            $this->line('   ❌ Fast database queries');
            $this->line('   ❌ Request correlation');
            $this->line('   ❌ Error fingerprinting');
            $this->line('   ❌ Metrics dashboard');
            $this->line('   ❌ Alerting system');

        } else {
            $this->info('ℹ️  You can enable/disable indexing later in config/log-platform.php');
        }

        $this->newLine();
        $this->info('✅ Log Platform installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Review config/log-platform.php');
        $this->line('2. Update config/logging.php to use custom handler');
        if ($useDatabase === 'yes') {
            $this->line('3. Run: php artisan log:index (to index existing logs)');
            $this->line('4. Start queue worker: php artisan queue:work');
        }
        $this->line('5. Visit: /log-platform in your browser');

        return self::SUCCESS;
    }

    protected function disableIndexingInConfig(): void
    {
        $configPath = config_path('log-platform.php');

        if (!file_exists($configPath)) {
            return;
        }

        $content = file_get_contents($configPath);
        $content = preg_replace(
            "/'enabled'\s*=>\s*env\('LOG_PLATFORM_INDEXING_ENABLED',\s*true\)/",
            "'enabled' => env('LOG_PLATFORM_INDEXING_ENABLED', false)",
            $content
        );

        file_put_contents($configPath, $content);
    }
}

