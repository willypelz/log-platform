<?php

namespace Willypelz\LogPlatform;

use Willypelz\LogPlatform\Console\Commands\LogInstallCommand;
use Willypelz\LogPlatform\Console\Commands\LogClearCommand;
use Willypelz\LogPlatform\Services\LogParser;
use Willypelz\LogPlatform\Contracts\LogParserInterface;
use Willypelz\LogPlatform\Services\StrategyManager;
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

        // Register services
        $this->app->singleton(StrategyManager::class);
        $this->app->singleton(Services\HostManager::class);
        $this->app->singleton(Services\FileOnlyLogReader::class);
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
                LogClearCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__ . '/../config/log-platform.php' => config_path('log-platform.php'),
            ], 'log-platform-config');

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
}

