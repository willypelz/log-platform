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
    | Indexing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how logs are indexed for fast searching.
    |
    */
    'indexing' => [
        'enabled' => env('LOG_PLATFORM_INDEXING_ENABLED', true),
        'chunk_size' => 65536, // 64KB
        'batch_size' => 1000, // Bulk insert batch size
        'queue' => env('LOG_PLATFORM_QUEUE', 'default'),
        'max_lines_per_job' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-Time Streaming
    |--------------------------------------------------------------------------
    |
    | Configure real-time log streaming.
    |
    */
    'streaming' => [
        'enabled' => env('LOG_PLATFORM_STREAMING_ENABLED', true),
        'driver' => env('LOG_PLATFORM_STREAMING_DRIVER', 'sse'), // sse or websocket
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting System
    |--------------------------------------------------------------------------
    |
    | Configure the alerting system for error notifications.
    |
    */
    'alerts' => [
        'enabled' => env('LOG_PLATFORM_ALERTS_ENABLED', true),
        'channels' => [
            'mail' => [
                'enabled' => true,
                'to' => env('LOG_PLATFORM_ALERT_EMAIL'),
            ],
            'slack' => [
                'enabled' => false,
                'webhook_url' => env('LOG_PLATFORM_SLACK_WEBHOOK'),
            ],
            'webhook' => [
                'enabled' => false,
                'url' => env('LOG_PLATFORM_WEBHOOK_URL'),
            ],
        ],
        'evaluation_interval' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics Configuration
    |--------------------------------------------------------------------------
    |
    | Configure metrics aggregation and rollups.
    |
    */
    'metrics' => [
        'enabled' => env('LOG_PLATFORM_METRICS_ENABLED', true),
        'rollup_intervals' => [60, 300, 3600], // 1m, 5m, 1h in seconds
        'aggregation_interval' => 60, // Run aggregation every 60 seconds
    ],

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
        'by_level' => [
            'debug' => 7,
            'info' => 14,
            'warning' => 30,
            'error' => 90,
            'critical' => 365,
        ],
        'by_environment' => [
            'local' => 7,
            'staging' => 30,
            'production' => 90,
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
        'gates' => [
            'view-logs' => true,
            'manage-alerts' => true,
        ],
        'allowed_environments' => ['local', 'staging', 'production'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Host Support
    |--------------------------------------------------------------------------
    |
    | Configure multiple hosts to aggregate logs from different servers.
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
    | Shareable Links
    |--------------------------------------------------------------------------
    */
    'shareable_links' => [
        'enabled' => true,
        'expires_days' => 7,
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
    */
    'additional_folders' => [],

    /*
    |--------------------------------------------------------------------------
    | Performance Tuning
    |--------------------------------------------------------------------------
    |
    | Advanced performance settings.
    |
    */
    'performance' => [
        'query_cache_ttl' => 60, // seconds
        'enable_query_log' => false,
        'max_memory_mb' => 256,
    ],
];

