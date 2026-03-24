<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Naming Strategy
    |--------------------------------------------------------------------------
    |
    | Choose the default strategy for log file naming:
    | - daily: laravel-2026-03-18.log
    | - weekly: laravel-2026-week-12.log
    | - monthly: laravel-2026-03.log
    | - custom: Use custom pattern or class
    |
    */
    'default_strategy' => env('LOG_PLATFORM_STRATEGY', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Custom Strategies
    |--------------------------------------------------------------------------
    |
    | Define custom naming strategies.
    |
    */
    'strategies' => [
        'custom' => [
            'pattern' => '{channel}-{Y}-{m}-{d}.log', // or class name
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the web UI.
    |
    */
    'ui' => [
        'theme' => env('LOG_PLATFORM_THEME', 'auto'), // light, dark, auto
        'refresh_ms' => 5000, // Auto-refresh interval
        'virtualization_buffer' => 10, // Extra rows to render for smooth scrolling
        'logs_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Configure access control and security settings.
    |
    */
    'security' => [
        'middleware' => ['api'],
        'ui_middleware' => ['web'],  // Middleware for web UI route
        'allowed_environments' => ['local', 'staging', 'production'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Directory / Multi-Host Support
    |--------------------------------------------------------------------------
    |
    | Add additional named paths here. All paths are file-system only.
    |
    */
    'hosts' => [
        'local' => [
            'name' => ucfirst(env('APP_ENV', 'local')),
            'path' => storage_path('logs'),
        ],
        // 'production' => [
        //     'name' => 'Production',
        //     'host' => 'https://prod.example.com',
        //     'path' => '/var/www/storage/logs',
        //     'auth' => ['user', 'pass'],
        //     'headers' => ['X-API-Key' => env('REMOTE_API_KEY')],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Management
    |--------------------------------------------------------------------------
    */
    'files' => [
        'allow_download' => env('LOG_PLATFORM_ALLOW_DOWNLOAD', true),
        'allow_delete' => env('LOG_PLATFORM_ALLOW_DELETE', true),
        'max_download_size_mb' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Type Detection
    |--------------------------------------------------------------------------
    */
    'log_types' => [
        'enabled' => true,
        'supported' => ['default', 'horizon', 'nginx', 'apache', 'redis', 'postgres', 'supervisor', 'php-fpm'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Folders
    |--------------------------------------------------------------------------
    |
    | Extra absolute paths to include alongside storage/logs.
    |
    */
    'additional_folders' => [],

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    |
    | Configure how long logs are kept.
    |
    */
    'retention' => [
        'enabled' => env('LOG_PLATFORM_RETENTION_ENABLED', false),
        'days' => 30,
    ],
];

