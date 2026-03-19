# Laravel Log Platform

A production-grade logging and monitoring platform for Laravel applications that extends Monolog with custom log generation strategies, high-performance indexing, real-time streaming, and advanced analytics.

## Features

### 🚀 Core Features
- **Custom Log Generation Strategies**: Configure daily, weekly, monthly, or custom naming patterns
- **High-Performance Indexing**: Chunked reading, lazy loading, and background indexing for GB-scale logs
- **Fast Modern UI**: Beautiful, responsive SPA with virtual scrolling and dark mode
- **Advanced Filtering**: Search by level, date range, keywords, and structured queries
- **Real-Time Streaming**: Live log updates using Server-Sent Events
- **Request Correlation**: Track all logs for a single request with unique IDs
- **Alerting System**: Trigger notifications based on error thresholds and patterns
- **Metrics Dashboard**: Visualize errors per minute, request latency, and trends
- **Multi-Environment Support**: Manage logs from local, staging, and production

## Installation

```bash
composer require willypelz/laravel-log-platform
```

### Interactive Installation

Run the install command and choose your mode:

```bash
php artisan log:install
```

You'll be asked: **"Do you want to use database indexing?"**
- **Yes** - Full features (recommended for production)
- **No** - Simple file-only mode (no migrations)
- **Decide later** - Configure manually

### Manual Installation

Publish the config file:
```bash
php artisan vendor:publish --tag=log-platform-config
```

**For Database Mode** (optional):
```bash
php artisan vendor:publish --tag=log-platform-migrations
php artisan migrate
```

**For File-Only Mode:**
```php
// config/log-platform.php
'indexing' => ['enabled' => false],
```

See [CHOOSING_YOUR_MODE.md](docs/CHOOSING_YOUR_MODE.md) for detailed comparison.

## Configuration

Edit `config/log-platform.php` to customize:

```php
return [
    'default_strategy' => 'daily', // daily, weekly, monthly, or custom
    'indexing' => [
        'enabled' => true,
        'chunk_size' => 65536, // 64KB
        'queue' => 'default',
    ],
    'streaming' => [
        'enabled' => true,
        'driver' => 'sse', // sse or websocket
    ],
    'alerts' => [
        'enabled' => true,
        'channels' => ['mail', 'slack'],
    ],
];
```

### Configure Custom Log Strategy

In your `config/logging.php`, add the custom handler:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['custom-daily'],
    ],
    
    'custom-daily' => [
        'driver' => 'custom',
        'via' => \Willypelz\LogPlatform\Handlers\StrategyRotatingFileHandler::class,
        'path' => storage_path('logs'),
        'level' => 'debug',
        'strategy' => 'daily', // or 'weekly', 'monthly', 'custom'
    ],
],
```

## Usage

### Index Logs
```bash
# Index all logs
php artisan log:index

# Index specific environment
php artisan log:index --env=production

# Rebuild index from scratch
php artisan log:index --rebuild

# Index from specific date
php artisan log:index --from=2026-03-01
```

### View Statistics
```bash
php artisan log:stats
```

### Clear Old Logs
```bash
# Clear logs older than 30 days
php artisan log:clear --days=30

# Clear specific level
php artisan log:clear --level=debug
```

### Access Web UI

Navigate to: `http://your-app.com/log-platform`

## API Endpoints

All endpoints are prefixed with `/log-platform/api`:

- `GET /logs` - List logs with filters
- `GET /logs/{id}` - Get single log entry
- `GET /logs/stream` - Real-time log streaming (SSE)
- `GET /requests/{request_id}/logs` - Get all logs for a request
- `GET /metrics/overview` - Get metrics overview
- `GET /metrics/timeseries` - Get time-series data
- `GET /alerts/rules` - List alert rules
- `POST /alerts/rules` - Create alert rule
- `POST /index/sync` - Trigger manual index sync

## Advanced Usage

### Structured Queries

Use structured query syntax for advanced filtering:

```
level:error AND user_id:123
level:error OR level:critical
message:"Database connection failed"
NOT level:debug
(level:error OR level:critical) AND env:production
```

### Custom Naming Strategy

Create a custom strategy class:

```php
use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;

class CustomNamingStrategy implements NamingStrategyInterface
{
    public function resolveFilename(\DateTimeInterface $date, string $channel): string
    {
        return sprintf(
            'laravel-%s-%s.log',
            $channel,
            $date->format('Y-m-d-H')
        );
    }
}
```

Register in config:
```php
'strategies' => [
    'custom' => CustomNamingStrategy::class,
],
```

## Architecture

- **Write Path**: Custom Monolog handlers with processors
- **Index Path**: Async tail-based parser with checkpointing
- **Query Path**: Indexed DB search with file fallback
- **Stream Path**: SSE/WebSocket broadcaster
- **Alert Path**: Rule engine with notification channels
- **Metrics Path**: Aggregate rollups for dashboards

## Performance

- Handles GB-scale log files
- Chunked reading (never loads entire files)
- Background queue processing
- Virtual scrolling for millions of entries
- Sub-second query response times

## Security

Configure middleware in `config/log-platform.php`:

```php
'security' => [
    'middleware' => ['web', 'auth', 'can:view-logs'],
    'allowed_environments' => ['local', 'staging', 'production'],
],
```

## License

MIT License

