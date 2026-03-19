<?php

namespace Willypelz\LogPlatform;

use Willypelz\LogPlatform\Console\Commands\LogInstallCommand;
use Willypelz\LogPlatform\Console\Commands\LogIndexCommand;
use Willypelz\LogPlatform\Console\Commands\LogClearCommand;
use Willypelz\LogPlatform\Console\Commands\LogStatsCommand;
use Willypelz\LogPlatform\Contracts\IndexerStoreInterface;
use Willypelz\LogPlatform\Contracts\LogParserInterface;
use Willypelz\LogPlatform\Contracts\QueryEngineInterface;
use Willypelz\LogPlatform\Services\DatabaseIndexerStore;
use Willypelz\LogPlatform\Services\LogIndexer;
use Willypelz\LogPlatform\Services\LogParser;
use Willypelz\LogPlatform\Services\LogQueryService;
use Willypelz\LogPlatform\Services\StrategyManager;
use Willypelz\LogPlatform\Services\StructuredQueryParser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class LogPlatformServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../config/log-platform.php', 'log-platform');

        // Register contracts
        $this->app->singleton(LogParserInterface::class, LogParser::class);
        $this->app->singleton(IndexerStoreInterface::class, DatabaseIndexerStore::class);
        $this->app->singleton(QueryEngineInterface::class, LogQueryService::class);

        // Register services
        $this->app->singleton(StrategyManager::class);
        $this->app->singleton(StructuredQueryParser::class);
        $this->app->singleton(LogQueryService::class);
        $this->app->singleton(Services\HostManager::class);
        $this->app->singleton(Services\FileOnlyLogReader::class);

        // Register LogIndexer with configuration
        $this->app->singleton(LogIndexer::class, function ($app) {
            return new LogIndexer(
                $app->make(LogParserInterface::class),
                $app->make(IndexerStoreInterface::class),
                config('log-platform.indexing.chunk_size', 65536),
                config('log-platform.indexing.batch_size', 1000)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                LogInstallCommand::class,
                LogIndexCommand::class,
                LogClearCommand::class,
                LogStatsCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/log-platform.php' => config_path('log-platform.php'),
            ], 'log-platform-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'log-platform-migrations');

            // Publish views (when we create them)
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/log-platform'),
            ], 'log-platform-views');

            // Publish assets (when we create them)
            $this->publishes([
                __DIR__ . '/../resources/js' => resource_path('js/vendor/log-platform'),
            ], 'log-platform-assets');
        }

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/log-platform.php');

        // Load views (when we create them)
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'log-platform');

        // Register custom Monolog handler
        $this->registerMonologHandler();

        // Schedule tasks
        $this->scheduleJobs();
    }

    /**
     * Register custom Monolog handler.
     */
    protected function registerMonologHandler(): void
    {
        Log::extend('custom', function ($app, $config) {
            return \Willypelz\LogPlatform\Handlers\StrategyRotatingFileHandler::fromConfig($config);
        });
    }

    /**
     * Schedule background jobs.
     */
    protected function scheduleJobs(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // Schedule metrics aggregation
        if (config('log-platform.metrics.enabled')) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

                $interval = config('log-platform.metrics.aggregation_interval', 60);

                $schedule->call(function () {
                    $env = config('app.env');
                    $bucketStart = now()->startOfMinute();

                    \Willypelz\LogPlatform\Jobs\AggregateMetricsJob::dispatch($env, $bucketStart);
                })->everyMinute();
            });
        }

        // Schedule alert evaluation
        if (config('log-platform.alerts.enabled')) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);

                $schedule->call(function () {
                    \Willypelz\LogPlatform\Jobs\EvaluateAlertsJob::dispatch();
                })->everyMinute();
            });
        }
    }
}

