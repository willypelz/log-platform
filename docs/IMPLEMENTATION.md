# Laravel Log Platform - Implementation Guide

## 🚀 Quick Start

### 1. Installation

```bash
# Install Composer dependencies
composer install

# Publish configuration and migrations
php artisan log:install

# Run migrations
php artisan migrate
```

### 2. Configure Logging

Edit `config/logging.php` to use the custom handler:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['custom-daily', 'stderr'],
        'ignore_exceptions' => false,
    ],
    
    'custom-daily' => [
        'driver' => 'custom',
        'path' => storage_path('logs'),
        'channel' => 'laravel',
        'level' => env('LOG_LEVEL', 'debug'),
        'strategy' => 'daily', // or 'weekly', 'monthly', 'custom'
        'bubble' => true,
    ],
],

'processors' => [
    \Willypelz\LogPlatform\Processors\RequestCorrelationProcessor::class,
    \Willypelz\LogPlatform\Processors\FingerprintProcessor::class,
    \Willypelz\LogPlatform\Processors\EnvironmentProcessor::class,
],
```

### 3. Index Existing Logs

```bash
# Index all log files
php artisan log:index

# Index with options
php artisan log:index --env=production --channel=laravel
php artisan log:index --from=2026-03-01 --rebuild
php artisan log:index --async  # Run in background queue
```

### 4. Set Up Queue Worker

For background indexing and metrics aggregation:

```bash
php artisan queue:work
```

### 5. Build Frontend Assets

```bash
cd resources/js
npm install
npm run build

# Or for development
npm run dev
```

### 6. Access the UI

Navigate to: `http://your-app.com/log-platform`

## 📋 Configuration

### Environment Variables

Add to your `.env`:

```env
LOG_PLATFORM_STRATEGY=daily
LOG_PLATFORM_INDEXING_ENABLED=true
LOG_PLATFORM_QUEUE=default
LOG_PLATFORM_STREAMING_ENABLED=true
LOG_PLATFORM_STREAMING_DRIVER=sse
LOG_PLATFORM_ALERTS_ENABLED=true
LOG_PLATFORM_ALERT_EMAIL=admin@example.com
LOG_PLATFORM_METRICS_ENABLED=true
LOG_PLATFORM_RETENTION_ENABLED=false
LOG_PLATFORM_THEME=auto
```

### Custom Naming Strategy

Create a custom strategy class:

```php
<?php

namespace App\Logging;

use Willypelz\LogPlatform\Contracts\NamingStrategyInterface;

class HourlyNamingStrategy implements NamingStrategyInterface
{
    public function resolveFilename(\DateTimeInterface $date, string $channel): string
    {
        return sprintf(
            '%s-%s.log',
            $channel,
            $date->format('Y-m-d-H')
        );
    }

    public function shouldRotate(\DateTimeInterface $lastWrite, \DateTimeInterface $currentWrite): bool
    {
        return $lastWrite->format('Y-m-d-H') !== $currentWrite->format('Y-m-d-H');
    }
}
```

Register in `config/log-platform.php`:

```php
'strategies' => [
    'hourly' => \App\Logging\HourlyNamingStrategy::class,
],
```

Use in `config/logging.php`:

```php
'custom-hourly' => [
    'driver' => 'custom',
    'path' => storage_path('logs'),
    'channel' => 'laravel',
    'strategy' => 'hourly',
],
```

## 🔧 Artisan Commands

### `log:install`
Install the package (publish config, migrations)

### `log:index`
Index log files for fast searching

Options:
- `--env=` : Environment to index
- `--channel=` : Channel to index (default: laravel)
- `--from=` : Index from date (Y-m-d)
- `--rebuild` : Rebuild index from scratch
- `--async` : Run in background queue

### `log:stats`
Show log statistics

Options:
- `--env=` : Filter by environment
- `--from=` : From date
- `--to=` : To date

### `log:clear`
Clear old log entries

Options:
- `--days=` : Clear logs older than N days (default: 30)
- `--level=` : Clear specific log level
- `--env=` : Clear specific environment
- `--files` : Also delete physical log files
- `--force` : Skip confirmation

## 🔌 API Reference

### Base URL
All endpoints are prefixed with `/log-platform/api`

### Authentication
Apply middleware in `config/log-platform.php`:

```php
'security' => [
    'middleware' => ['web', 'auth', 'can:view-logs'],
],
```

### Endpoints

#### `GET /logs`
List logs with filters

Query Parameters:
- `query` : Structured query (e.g., `level:error AND user_id:123`)
- `env` : Filter by environment
- `level` : Filter by log level
- `from` : From datetime
- `to` : To datetime
- `keyword` : Keyword search
- `request_id` : Filter by request ID
- `fingerprint` : Filter by error fingerprint
- `limit` : Results per page (max 500)
- `cursor` : Pagination cursor

Response:
```json
{
  "data": [...],
  "cursor": "12345",
  "hasMore": true
}
```

#### `GET /logs/{id}`
Get single log entry

#### `GET /requests/{requestId}/logs`
Get all logs for a request

#### `GET /fingerprints/{fingerprint}/logs`
Get all logs for an error fingerprint

#### `GET /logs/stream`
Real-time log streaming (SSE)

Query Parameters:
- `env` : Filter by environment
- `level` : Filter by log level

#### `GET /metrics/overview`
Get metrics overview

Query Parameters:
- `env` : Environment
- `from` : From datetime
- `to` : To datetime

#### `GET /metrics/timeseries`
Get time-series metrics

Query Parameters:
- `metric` : Metric name (e.g., `errors_per_minute`)
- `env` : Environment
- `from` : From datetime
- `to` : To datetime
- `bucket_size` : Bucket size in seconds

#### `GET /alerts/rules`
List alert rules

#### `POST /alerts/rules`
Create alert rule

Body:
```json
{
  "name": "High Error Rate",
  "query": "level:error",
  "window_seconds": 60,
  "threshold_count": 10,
  "channels": ["mail", "slack"],
  "cooldown_seconds": 300,
  "enabled": true
}
```

#### `PATCH /alerts/rules/{id}`
Update alert rule

#### `DELETE /alerts/rules/{id}`
Delete alert rule

## 📊 Structured Query Syntax

Use structured queries for advanced filtering:

```
level:error                          # Single level
level:error OR level:critical        # Multiple levels
level:error AND user_id:123          # Multiple conditions
message:"connection failed"          # Phrase search
NOT level:debug                      # Negation
(level:error OR level:critical) AND env:production  # Grouping
```

## 🎯 Performance Tips

1. **Enable Queue Workers**: Run background workers for indexing
   ```bash
   php artisan queue:work --queue=default
   ```

2. **Index Regularly**: Schedule indexing in `app/Console/Kernel.php`
   ```php
   $schedule->command('log:index --async')->hourly();
   ```

3. **Clean Old Logs**: Schedule cleanup
   ```php
   $schedule->command('log:clear --days=30 --files')->daily();
   ```

4. **Database Optimization**: Add indexes if needed
   ```sql
   CREATE INDEX idx_custom ON log_platform_indexed_logs (custom_field);
   ```

5. **Use Redis for Cache**: Configure in `config/log-platform.php`

## 🔒 Security

### Middleware
Protect routes with middleware:

```php
'security' => [
    'middleware' => ['web', 'auth', 'can:view-logs'],
],
```

### Gates
Define custom gates in `AuthServiceProvider`:

```php
Gate::define('view-logs', function ($user) {
    return $user->hasRole('admin');
});
```

### Environment Filtering
Restrict visible environments:

```php
'security' => [
    'allowed_environments' => ['production', 'staging'],
],
```

## 🐛 Troubleshooting

### Logs Not Indexing
1. Check queue worker is running
2. Check file permissions on `storage/logs`
3. Run `php artisan log:index --rebuild`
4. Check database connection

### Performance Issues
1. Increase `chunk_size` in config
2. Reduce `batch_size` if memory issues
3. Add database indexes
4. Enable query caching

### SSE Not Working
1. Check server supports SSE (not all do)
2. Check `LOG_PLATFORM_STREAMING_ENABLED=true`
3. Check nginx/apache configuration for streaming
4. Use WebSocket driver as alternative

## 📈 Monitoring

Monitor the package itself:

```bash
# View indexing status
php artisan log:stats

# Check queue jobs
php artisan queue:monitor

# View failed jobs
php artisan queue:failed
```

## 🧪 Testing

Run package tests:

```bash
composer test
```

## 📝 License

MIT License - See LICENSE file for details

